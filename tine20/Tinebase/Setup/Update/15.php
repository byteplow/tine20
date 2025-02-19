<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Tinebase_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE015_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE015_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE015_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE015_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE015_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE015_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE015_UPDATE012 = __CLASS__ . '::update012';
    const RELEASE015_UPDATE013 = __CLASS__ . '::update013';
    const RELEASE015_UPDATE014 = __CLASS__ . '::update014';
    const RELEASE015_UPDATE015 = __CLASS__ . '::update015';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            // as we do a raw query, we dont want that table to be changed before we do our query => prio struct
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE015_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE015_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE015_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE015_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE015_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE015_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
            self::RELEASE015_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
            self::RELEASE015_UPDATE013          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update013',
            ],
            self::RELEASE015_UPDATE014          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update014',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE          => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE015_UPDATE015          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update015',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Tinebase', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_Tree_FileObject::class]);
        $this->addApplicationUpdate('Tinebase', '15.1', self::RELEASE015_UPDATE001);
    }

    // as we do a raw query, we dont want that table to be changed before we do our query => prio struct
    public function update002()
    {
        $db = $this->getDb();
        foreach ($db->query('SELECT role_id, application_id from ' . SQL_TABLE_PREFIX . 'role_rights WHERE `right` = "'
                . Tinebase_Acl_Rights_Abstract::RUN . '"')->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            $db->query($db->quoteInto('INSERT INTO ' . SQL_TABLE_PREFIX . 'role_rights SET role_id = ?', $row[0]) .
                $db->quoteInto(', application_id = ?', $row[1]) .
                ', `right` = "' . Tinebase_Acl_Rights_Abstract::MAINSCREEN . '", `id` = "' .
                Tinebase_Record_Abstract::generateUID() . '"');
        }
        $this->addApplicationUpdate('Tinebase', '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_MunicipalityKey::class]);
        $this->addApplicationUpdate('Tinebase', '15.3', self::RELEASE015_UPDATE003);
    }

    public function update004()
    {
        if ($this->getTableVersion('importexport_definition') < 14) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>skip_upstream_updates</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>'));
            $this->setTableVersion('tags', 14);
        };

        $this->addApplicationUpdate('Tinebase', '15.4', self::RELEASE015_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_CostCenter::class,
            Tinebase_Model_CostUnit::class,
        ]);

        $pfInit = new Tinebase_Setup_Initialize();
        $pfInit->_initializePF();

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.5', self::RELEASE015_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_MunicipalityKey::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.6', self::RELEASE015_UPDATE006);
    }

    public function update007()
    {
        if ($this->getTableVersion('importexport_definition') < 14) {
            $this->setTableVersion('importexport_definition', 14);
        };
        if ($this->getTableVersion('tags') == 14) {
            $this->setTableVersion('tags', 10);
        };
        if (!$this->_backend->columnExists('filter', 'importexport_definition')) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>filter</name>
                    <type>text</type>
                    <length>16000</length>
                </field>'));
        }
        $this->setTableVersion('importexport_definition', 15);
        $this->addApplicationUpdate('Tinebase', '15.7', self::RELEASE015_UPDATE007);
    }

    public function update008()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.8', self::RELEASE015_UPDATE008);
    }

    public function update009()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_MunicipalityKey::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.9', self::RELEASE015_UPDATE009);
    }
    
    public function update010()
    {
        $tables = [];
        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            $tables = array_merge($tables, Tinebase_Application::getInstance()->getApplicationTables($app));
        }

        $models = Tinebase_Application::getInstance()->getModelsOfAllApplications(true);
        asort($models);
        /** @var Tinebase_Record_Interface $model */
        foreach ($models as $model) {
            if (!($mc = $model::getConfiguration()) || !($tableName = $mc->getTableName())) {
                continue;
            }

            if (!in_array($tableName, $tables)) {
                list($app) = explode('_', $model);
                Tinebase_Application::getInstance()->addApplicationTable($app, $tableName, $mc->getVersion() ?: 1);
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.10', self::RELEASE015_UPDATE010);
    }

    public function update011()
    {
        if ($this->getTableVersion('config') < 2) {
            $this->_backend->alterCol('config', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>value</name>
                    <type>text</type>
                </field>'));
            $this->setTableVersion('config', 2);
        }
        $this->addApplicationUpdate('Tinebase', '15.11', self::RELEASE015_UPDATE011);
    }

    public function update012()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('groups') < 10) {
            $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['is_deleted' => 0], 'is_deleted IS NULL');
            $this->_backend->alterCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>'));
            foreach ($this->getDb()->query('SELECT GROUP_CONCAT(id), count(*) as c FROM ' . SQL_TABLE_PREFIX .
                    'groups GROUP BY name HAVING c > 1')->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                $date = new Tinebase_DateTime('1970-01-01 00:00:01');
                foreach (explode(',', $row[0]) as $num => $id) {
                    if (0 === $num) continue;
                    $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['deleted_time' => $date->toString()], $this->getDb()->quoteInto('id = ?', $id));
                    $date->addSecond(1);
                }
            }
            $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['deleted_time' => '1970-01-01 00:00:00'], 'deleted_time IS NULL');
            $this->_backend->alterCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                    <default>1970-01-01 00:00:00</default>
                </field>'));
            $this->setTableVersion('groups', 10);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.12', self::RELEASE015_UPDATE012);
    }

    public function update013()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->_backend->dropForeignKey('notes', 'notes::note_type_id--note_types::id');
        $this->_backend->dropTable('note_types');

        $db = Tinebase_Core::getDb();
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE . '" WHERE note_type_id = "1"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_TELEPHONE . '" WHERE note_type_id = "2"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_EMAIL . '" WHERE note_type_id = "3"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED . '" WHERE note_type_id = "4"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED . '" WHERE note_type_id = "5"');

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.13', self::RELEASE015_UPDATE013);
    }

    public function update014()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $app->order = 0;
        Tinebase_Application::getInstance()->updateApplication($app);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.14', self::RELEASE015_UPDATE014);
    }

    public function update015()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = Tinebase_Core::getDb();

        $rows = $db->query('SELECT * FROM ' . SQL_TABLE_PREFIX .
            'relations WHERE rel_id like "ext-gen%" and is_deleted=0')->fetchAll();
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Fixing broken relations: ' . count($rows));
        $rowsbyRelId = [];
        foreach ($rows as $row) {
            $rowsbyRelId[$row['rel_id']][] = $row;
        }

        foreach ($rowsbyRelId as $relId => $relations) {
//            echo '---------------------------------------' . "\n";
//            echo 'rel_id: ' . $relId . "\n";
//            echo 'number of relations: ' . count($relations). "\n";
            if (count($relations) === 2) {
                // just replace rel_ids with new UUID
                $query = 'UPDATE ' . SQL_TABLE_PREFIX . 'relations SET rel_id = "'
                    . Tinebase_Record_Abstract::generateUID() . '",last_modified_time=NOW() WHERE rel_id = "' . $relId . '";';
                // echo "$query \n";
                $db->query($query);
            } else {
                $updatedIds = [];
                foreach ($relations as $relation) {
                    if (! in_array($relation['related_id'], $updatedIds) && ! in_array($relation['own_id'], $updatedIds)) {
                        $query = 'UPDATE ' . SQL_TABLE_PREFIX . 'relations SET rel_id = "'
                            . Tinebase_Record_Abstract::generateUID() . '",last_modified_time=NOW() WHERE rel_id = "'
                            . $relId . '" AND (related_id = "' . $relation['related_id']. '" OR related_id = "' . $relation['own_id']. '");';
                        // echo "$query \n";
                        $db->query($query);
                        $updatedIds[] = $relation['own_id'];
                        $updatedIds[] = $relation['related_id'];
                    }
                }
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.15', self::RELEASE015_UPDATE015);
    }
}
