<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Timemachine_ModificationLogTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Timemachine_ModificationLog
     */
    protected $_modLogClass;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_logEntries;
    
    /**
     * @var Tinebase_Record_RecordSet
     * Persistant Records we need to cleanup at tearDown()
     */
    protected $_persistantLogEntries;
    
    /**
     * @var array holds recordId's we create log entries for
     */
    protected $_recordIds = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Timemachine_ModificationLogTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Lets update a record tree times
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $now = new Tinebase_DateTime();
        $this->_modLogClass = Tinebase_Timemachine_ModificationLog::getInstance();
        $this->_persistantLogEntries = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        $this->_recordIds = array('5dea69be9c72ea3d263613277c3b02d529fbd8bc');
        
        $tinebaseApp = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        
        $this->_logEntries = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array(
        array(
            'application_id'       => $tinebaseApp,
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now)->addDay(-2),
            'modification_account' => 7,
            'modified_attribute'   => 'FirstTestAttribute',
            'old_value'            => 'Hamburg',
            'new_value'            => 'Bremen'
        ),
        array(
            'application_id'       => $tinebaseApp,
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now)->addDay(-1),
            'modification_account' => 7,
            'modified_attribute'   => 'FirstTestAttribute',
            'old_value'            => 'Bremen',
            'new_value'            => 'Frankfurt'
        ),
        array(
            'application_id'       => $tinebaseApp,
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now),
            'modification_account' => 7,
            'modified_attribute'   => 'FirstTestAttribute',
            'old_value'            => 'Frankfurt',
            'new_value'            => 'Stuttgart'
        ),
        array(
            'application_id'       => $tinebaseApp,
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now)->addDay(-2),
            'modification_account' => 7,
            'modified_attribute'   => 'SecondTestAttribute',
            'old_value'            => 'Deutschland',
            'new_value'            => 'Östereich'
        ),
        array(
            'application_id'       => $tinebaseApp,
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now)->addDay(-1)->addSecond(1),
            'modification_account' => 7,
            'modified_attribute'   => 'SecondTestAttribute',
            'old_value'            => 'Östereich',
            'new_value'            => 'Schweitz'
        ),
        array(
            'application_id'       => $tinebaseApp->getId(),
            'record_id'            => $this->_recordIds[0],
            'record_type'          => 'TestType',
            'record_backend'       => 'TestBackend',
            'modification_time'    => $this->_cloner($now),
            'modification_account' => 7,
            'modified_attribute'   => 'SecondTestAttribute',
            'old_value'            => 'Schweitz',
            'new_value'            => 'Italien'
        )), true, false);
        
        
        foreach ($this->_logEntries as $logEntry) {
            /*$id = */$this->_modLogClass->setModification($logEntry);
            $this->_persistantLogEntries->addRecord($logEntry/*$this->_modLogClass->getModification($id)*/);
        }
    }

    /**
     * cleanup database
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }

    /**
     * tests that the returned mod logs equal the initial ones we defined 
     * in this test setup.
     * If this works, also the setting of logs works!
     *
     */
    public function testGetModification()
    {
        foreach ($this->_logEntries as $num => $logEntry) {
            $rawLogEntry = $logEntry->toArray();
            $rawPersistantLogEntry = $this->_persistantLogEntries[$num]->toArray();
            
            foreach ($rawLogEntry as $field => $value) {
                $persistantValue = $rawPersistantLogEntry[$field];
                if ($value != $persistantValue) {
                    $this->fail("Failed asserting that contents of saved LogEntry #$num in field $field equals initial datas. \n" . 
                                "Expected '$value', got '$persistantValue'");
                }
            }
        }
        $this->assertTrue(true);
    }
    
    /**
     * tests computation of a records differences described by a set of modification logs
     */
    public function testComputeDiff()
    {
        $diff = $this->_modLogClass->computeDiff($this->_persistantLogEntries);
        $this->assertEquals(2, count($diff->diff)); // we changed two attributes
        $changedAttributes = Tinebase_Timemachine_ModificationLog::getModifiedAttributes($this->_persistantLogEntries);
        foreach ($changedAttributes as $attrb) {
            switch ($attrb) {
               case 'FirstTestAttribute':
                   $this->assertEquals('Hamburg', $diff->oldData[$attrb]);
                   $this->assertEquals('Stuttgart', $diff->diff[$attrb]);
                   break;
               case 'SecondTestAttribute':
                   $this->assertEquals('Deutschland', $diff->oldData[$attrb]);
                   $this->assertEquals('Italien', $diff->diff[$attrb]);
            }
        }
    }
    
    /**
     * get modifications test
     */
    public function testGetModifications()
    {
        $testBase = array(
            'record_id' => '5dea69be9c72ea3d263613277c3b02d529fbd8bc',
            'type'      => 'TestType',
            'backend'   => 'TestBackend'
        );
        $firstModificationTime = $this->_persistantLogEntries[0]->modification_time;
        $lastModificationTime  = $this->_persistantLogEntries[count($this->_persistantLogEntries)-1]->modification_time;
        
        $toTest[] = $testBase + array(
            'from_add'  => 'addDay,-3',
            'until_add' => 'addDay,1',
            'nums'      => 6
        );
        $toTest[] = $testBase + array(
            'nums'  => 4
        );
        $toTest[] = $testBase + array(
            'account' => Tinebase_Record_Abstract::generateUID(),
            'nums'    => 0
        );
        
        foreach ($toTest as $params) {
            $from = clone $firstModificationTime;
            $until = clone $lastModificationTime;
            
            if (isset($params['from_add'])) {
               list($fn,$p) = explode(',', $params['from_add']);
               $from->$fn($p);
            }
            if (isset($params['until_add'])) {
                list($fn,$p) = explode(',', $params['until_add']);
                $until->$fn($p);
            }
            
            $account = isset($params['account']) ? $params['account'] : NULL;
            $diffs = $this->_modLogClass->getModifications('Tinebase', $params['record_id'], $params['type'], $params['backend'], $from, $until, $account);
            $count = 0;
            foreach ($diffs as $diff) {
                if ($diff->record_id == $params['record_id']) {
                   $count++;
                }
            }
            $this->assertEquals($params['nums'], $diffs->count());
        }
    }
    
    /**
     * test modlog undo
     * 
     * @see 0006252: allow to undo history items (modlog)
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function testUndo()
    {
        // create a record
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'tel_cell' => '+491234',
        )));
        // change something using the record controller
        $contact->tel_cell = NULL;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        // fetch modlog and test seq
        /** @var Tinebase_Model_ModificationLog $modlog */
        $modlog = $this->_modLogClass->getModifications('Addressbook', $contact->getId(), NULL, 'Sql',
            Tinebase_DateTime::now()->subSecond(5), Tinebase_DateTime::now())->getLastRecord();
        $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value, true));
        $this->assertTrue($modlog !== NULL);
        $this->assertEquals(2, $modlog->seq);
        $this->assertEquals('+491234', $diff->oldData['tel_cell']);

        // delete
        Addressbook_Controller_Contact::getInstance()->delete($contact->getId());
        
        $filter = new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'record_type',         'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'),
            array('field' => 'record_id',           'operator' => 'equals', 'value' => $contact->getId()),
            array('field' => 'modification_time',   'operator' => 'within', 'value' => 'weekThis'),
            array('field' => 'change_type',         'operator' => 'not', 'value' => Tinebase_Timemachine_ModificationLog::CREATED)
        ));

        $result = $this->_modLogClass->undo($filter, true);
        $this->assertEquals(2, $result['totalcount'], 'did not get 2 undone modlog: ' . print_r($result, TRUE));
        
        // check record after undo
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact);
        $this->assertEquals('+491234', $contact->tel_cell);
    }
    
    /**
     * purges mod log entries of given recordIds
     *
     * @param mixed [string|array|Tinebase_Record_RecordSet] $_recordIds
     * 
     * @todo should be removed when other tests do not need this anymore
     */
    public static function purgeLogs($_recordIds)
    {
        $table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'timemachine_modlog'));
        
        foreach ((array) $_recordIds as $recordId) {
             $table->delete($table->getAdapter()->quoteInto('record_id = ?', $recordId));
        }
    }
    
    /**
     * Workaround as the php clone operator does not return cloned 
     * objects right hand sided
     *
     * @param object $_object
     * @return object
     */
    protected function _cloner($_object)
    {
        return clone $_object;
    }
    
    /**
     * testDateTimeModlog
     * 
     * @see 0000996: add changes in relations/linked objects to modlog/history
     */
    public function testDateTimeModlog()
    {
        $task = Tasks_Controller_Task::getInstance()->create(new Tasks_Model_Task(array(
            'summary' => 'test task',
        )));
        
        $task->due = Tinebase_DateTime::now();
        /*$updatedTask = */Tasks_Controller_Task::getInstance()->update($task);
        
        $task->seq = 1;
        $modlog = $this->_modLogClass->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            $task, 2);

        $diff = new Tinebase_Record_Diff(json_decode($modlog->getFirstRecord()->new_value, true));
        $this->assertEquals(1, count($modlog));
        $this->assertEquals((string) $task->due, (string)($diff->diff['due']), 'new value mismatch: ' . print_r($modlog->toArray(), TRUE));
    }
}
