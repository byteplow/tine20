<?php
/**
 * controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for CRM application
 * 
 * @package     Crm
 */
class Crm_Controller extends Tinebase_Container_Abstract
{
    /**
     * CRM backend class
     *
     * @var Crm_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * get lead sources
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadsource
     */
    public function getLeadSources($_sort = 'id', $_dir = 'ASC')
    {
        $result = $this->_backend->getLeadSources($_sort, $_dir);

        return $result;    
    }

    /**
     * save leadsources
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadsources(Tinebase_Record_Recordset $_leadSources)
    {
        $result = $this->_backend->saveLeadsources($_leadSources);
        
        return $result;
    }  
    
    /**
     * get lead types
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadtypes($_sort, $_dir)
    {
        $result = $this->_backend->getLeadtypes($_sort, $_dir);

        return $result;    
    }    
    
   /**
     * save Leadtypes
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadtypes(Tinebase_Record_Recordset $_leadTypes)
    {
        $result = $this->_backend->saveLeadtypes($_leadTypes);
        
        return $result;
    }      
    
    /**
     * get products available
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getProductsAvailable($_sort, $_dir)
    {
        $result = $this->_backend->getProductsAvailable($_sort, $_dir);

        return $result;    
    }     

   /**
     * save Productsource
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProductSource(Tinebase_Record_Recordset $_productSource)
    {
        $result = $this->_backend->saveProductsource($_productSource);
    } 

    /**
     * get lead states
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadstates($_sort, $_dir)
    {
        $result = $this->_backend->getLeadstates($_sort, $_dir);

        return $result;    
    }

    /**
     * get one state identified by id
     *
     * @return Crm_Model_Leadstate
     */
    public function getLeadState($_id)
    {
        $result = $this->_backend->getLeadState($_id);

        return $result;    
    }

    /**
     * get one leadsource identified by id
     *
     * @return Crm_Model_Leadsource
     */
    public function getLeadSource($_sourceId)
    {
        $result = $this->_backend->getLeadSource($_sourceId);

        return $result;    
    }
    
    /**
     * get one leadtype identified by id
     *
     * @return Crm_Model_Leadtype
     */
    public function getLeadType($_typeId)
    {
        $result = $this->_backend->getLeadType($_typeId);

        return $result;    
    }
    
    /**
     * delete products (belonging to one lead)
     *
     * @param string $_id
     *
     * @return array
     */
    public function deleteProducts($_id)
    {
        $result = $this->_backend->deleteProducts($_id);

        return $result;    
    }     


   /**
     * save Leadstates
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadstates(Tinebase_Record_Recordset $_leadStates)
    {
        $result = $backend->saveLeadstates($_leadStates);
        
        return $result;
    } 
  

    /**
     * save Contacts
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveContacts(array $_contacts, $_id)
    {        
        $result = $this->_backend->saveContacts($_contacts, $_id);
        
        return $result;
    }   
  
    /**
     * save Products
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProducts(Tinebase_Record_Recordset $_productData)
    {
        $result = $this->_backend->saveProducts($_productData);
        
        return $result;
    }   
    
    public function getLinks($_leadId, $_application = NULL)
    {
        $links = Tinebase_Links::getInstance()->getLinks('crm', $_leadId, $_application);
        
        return $links;
    }
    
    public function setLinkedCustomer($_leadId, array $_contactIds)
    {
        $result = Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'customer');
        
        return $result;
    }

    public function setLinkedPartner($_leadId, array $_contactIds)
    {
        $result = Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'partner');
        
        return $result;
    }

    public function setLinkedAccount($_leadId, array $_contactIds)
    {
        $result = Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'account');
        
        return $result;
    }
    
    public function setLinkedTasks($_leadId, array $_taskIds)
    {
        $result = Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'tasks', $_taskIds, '');
        
        return $result;
    }
    
    /**
     * get total count of all leads
     *
     * @return int count of all leads
     */
    public function getCountOfAllLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $result = $this->_backend->getCountOfAllLeads($_filter, $_state, $_probability, $_getClosedLeads);
        
        return $result;
    }

    /**
     * get total count of leads from shared folders
     *
     * @return int count of shared leads
     */
    public function getCountOfSharedLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        return $backend->getCountOfSharedLeads($_filter, $_state, $_probability, $_getClosedLeads);
    }

    /**
     * get total count of leads from other users
     *
     * @return int count of shared leads
     */
    public function getCountOfOtherPeopleLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        return $backend->getCountOfOtherPeopleLeads($_filter, $_state, $_probability, $_getClosedLeads);
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param Tinebase_Account_Model_Account $_account the accountd object
     * @return Tinebase_Record_RecordSet of type Tinebase_Model_Container
     */
    public function createPersonalFolder(Tinebase_Account_Model_Account $_account)
    {
        $personalContainer = Tinebase_Container::getInstance()->addPersonalContainer($_account->accountId, 'crm', 'Personal Leads');
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }

    
    
    
    
   /**
     * add Lead
     *
     * @param Crm_Model_Lead $_lead the lead to add
     * @return Crm_Model_Lead the newly added lead
     */ 
    public function addLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_lead->container, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }
        
        $lead = $this->_backend->addLead($_lead);
        
        $this->sendNotifications(false, $lead);
        
        return $lead;
    }     
        
    /**
     * delete a lead
     *
     * @param int|array|Tinebase_Record_RecordSet|Crm_Model_Lead $_leadId
     * @return void
     */
    public function deleteLead($_leadId)
    {
        if(is_array($_leadId) or $_leadId instanceof Tinebase_Record_RecordSet) {
            foreach($_leadId as $leadId) {
                $this->deleteLead($leadId);
            }
        } else {
            $lead = $this->_backend->getLead($_leadId);
            if(Zend_Registry::get('currentAccount')->hasGrant($lead->container, Tinebase_Container::GRANT_DELETE)) {
                $this->_backend->deleteLead($_leadId);
            } else {
                throw new Exception('delete access to lead denied');
            }
        }
    }
    
    /**
     * returns an empty lead with some defaults set
     *
     * @return Crm_Model_Lead
     */
    public function getEmptyLead()
    {
        $defaultState  = (isset(Zend_Registry::get('configFile')->crm->defaultstate) ? Zend_Registry::get('configFile')->crm->defaultstate : 1);
        $defaultType   = (isset(Zend_Registry::get('configFile')->crm->defaulttype) ? Zend_Registry::get('configFile')->crm->defaulttype : 1);
        $defaultSource = (isset(Zend_Registry::get('configFile')->crm->defaultsource) ? Zend_Registry::get('configFile')->crm->defaultsource : 1);
        
        $defaultData = array(
            'leadstate_id'   => $defaultState,
            'leadtype_id'    => $defaultType,
            'leadsource_id'  => $defaultSource,
            'start'          => Zend_Date::now(),
            'probability'    => 0
        );
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($defaultData, true));
        $emptyLead = new Crm_Model_Lead($defaultData, true);
        
        return $emptyLead;
    }
    
    /**
     * get all leads, filtered by different criteria
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @param int $_state
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);
        
        if(count($allContainer) === 0) {
            $this->createPersonalFolder(Zend_Registry::get('currentAccount'));
            $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getLeads($containerIds, $_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads);

        return $result;
    }
    
    /**
     * get all shared leads, filtered by different criteria
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @param int $_state
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getSharedLeads($_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('crm', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Crm_Model_Lead');
        }
        
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getLeads($containerIds, $_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads);

        return $result;
    }
    
    /**
     * get all other people leads, filtered by different criteria
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @param int $_state
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getOtherPeopleLeads($_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('crm', Tinebase_Container::GRANT_READ);
                
        if(count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Crm_Model_Lead');
        }
        
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getLeads($containerIds, $_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads);

        return $result;
    }
    
    /**
     * get lead identified by leadId
     *
     * @param int $_leadId
     * @return Crm_Model_Lead
     */
    public function getLead($_leadId)
    {
        $lead = $this->_backend->getLead($_leadId);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($lead->container, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read permission to lead denied');
        }
        
        return $lead;
    }
    
   /**
     * update Lead
     *
     * @param Crm_Model_Lead $_lead the lead to update
     * @return Crm_Model_Lead the updated lead
     */ 
    public function updateLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_lead->container, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }
        
        $lead = $this->_backend->updateLead($_lead);
        
        $this->sendNotifications(true, $lead);
        
        return $lead;
    }
         
    /**
     * creates notification text and sends out notifications
     *
     * @param bool $_isUpdate set to true(lead got updated) or false(lead got added)
     * @param Crm_Model_Lead $_lead
     * @return void
     */
    protected function sendNotifications($_isUpdate, Crm_Model_Lead $_lead)
    {
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views');
        
        $view->updater = Zend_Registry::get('currentAccount');
        $view->lead = $_lead;
        $view->leadState = $this->getLeadState($_lead->leadstate_id);
        $view->leadType = $this->getLeadType($_lead->leadtype_id);
        $view->leadSource = $this->getLeadSource($_lead->leadsource_id);
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container);
        
        if($_lead->start instanceof Zend_Date) {
            $view->start = $_lead->start->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->start = '-';
        }
        
        if($_lead->end instanceof Zend_Date) {
            $view->leadEnd = $_lead->end->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if($_lead->end_scheduled instanceof Zend_Date) {
            $view->ScheduledEnd = $_lead->end_scheduled->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->ScheduledEnd = '-';
        }
        
        #$translate = new Zend_Translate('gettext', 'Crm/translations/de.mo', 'de');
        $translate = new Zend_Translate('gettext', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale(Zend_Registry::get('locale'));
        
        $view->lang_state = $translate->_('State');
        $view->lang_type = $translate->_('Type');
        $view->lang_source = $translate->_('Source');
        $view->lang_start = $translate->_('Start');
        $view->lang_scheduledEnd = $translate->_('Scheduled end');
        $view->lang_end = $translate->_('End');
        $view->lang_turnover = $translate->_('Turnover');
        $view->lang_probability = $translate->_('Probability');
        $view->lang_folder = $translate->_('Folder');
        $view->lang_updatedBy = $translate->_('Updated by');
        
        $plain = $view->render('newLeadPlain.php');
        $html = $view->render('newLeadHtml.php');
        
        if($_isUpdate === true) {
            $subject = $translate->_('Lead updated') . ': ' . $_lead->lead_name;
        } else {
            $subject = $translate->_('Lead added') . ': ' . $_lead->lead_name;
        }
        
        // send notifications to all accounts in the first step
        $accounts = Tinebase_Account::getInstance()->getFullAccounts();
        Tinebase_Notification::getInstance()->send(Zend_Registry::get('currentAccount'), $accounts, $subject, $plain, $html);
    }
    
    /**
     * converts a int, string or Crm_Model_Lead to a lead id
     *
     * @param int|string|Crm_Model_Lead $_accountId the lead id to convert
     * @return int
     */
    static public function convertLeadIdToInt($_leadId)
    {
        if($_leadId instanceof Crm_Model_Lead) {
            if(empty($_leadId->id)) {
                throw new Exception('no lead id set');
            }
            $id = (int) $_leadId->id;
        } else {
            $id = (int) $_leadId;
        }
        
        if($id === 0) {
            throw new Exception('lead id can not be 0');
        }
        
        return $id;
    }
}