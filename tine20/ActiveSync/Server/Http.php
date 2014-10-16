<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * http server
 *
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_Http extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const REQUEST_TYPE = 'ActiveSync';
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
        $this->_body    = $this->_getBody($body);
        
        try {
            list($loginName, $password) = $this->_getAuthData($this->_request);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' is ActiveSync request.');
        
        Tinebase_Core::initFramework();
        
        try {
            $authResult = $this->_authenticate(
                $loginName,
                $password,
                $this->_request
            );
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $authResult = false;
        }
        
        if ($authResult !== true) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if (!$this->_checkUserPermissions($loginName)) {
            return;
        }
        
        $this->_initializeRegistry();
        
        $request = new Zend_Controller_Request_Http(
            Zend_Uri::factory('http://localhost' . $this->_request->getUriString())
        );
        
        $syncFrontend = new Syncroton_Server(Tinebase_Core::getUser()->accountId, $request, $this->_body);
        
        $syncFrontend->handle();
        
        Tinebase_Controller::getInstance()->logout();
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        return ($this->_request) ? $this->_request->getMethod() : NULL;
    }
    
    /**
     * get body
     * 
     * @param resource $body used mostly for unittesting
     * @return resource
     * 
     * @todo 0007504: research input stream problems / remove the hotfix afterwards
     */
    protected function _getBody($body)
    {
        if ($body === null) {
            // FIXME: this is a hotfix for 0007454: no email reply or forward (iOS/android 4.1.1)
            // the wbxml decoder seems to run into problems when we just pass the input stream
            // when the stream is copied first, the problems disappear
            //$this->_body    = $body !== null ? $body : fopen('php://input', 'r');
            $tempStream = fopen("php://temp", 'r+');
            stream_copy_to_stream(fopen('php://input', 'r'), $tempStream);
            rewind($tempStream);
            // file_put_contents(tempnam('/var/tmp', 'wbxml'), $tempStream); // for debugging
            return $tempStream;
        } else {
            return $body;
        }
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    protected function _authenticate($_username, $_password, \Zend\Http\Request $request)
    {
        $pos = strrchr($_username, '\\');
        
        if($pos !== false) {
            $username = substr(strrchr($_username, '\\'), 1);
        } else {
            $username = $_username;
        }
        
        return Tinebase_Controller::getInstance()->login(
            $username,
            $_password,
            $request,
            self::REQUEST_TYPE
        );
    }
    
    /**
     * check user permissions
     * 
     * @param string $loginName
     * @return boolean
     */
    protected function _checkUserPermissions($loginName)
    {
        try {
            $activeSync = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync');
        } catch (Tinebase_Exception_NotFound $e) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not installed');
            return false;
        }
        
        if ($activeSync->status != 'enabled') {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled');
            return false;
        }
        
        if (Tinebase_Core::getUser()->hasRight($activeSync, Tinebase_Acl_Rights::RUN) !== true) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $loginName);
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled for account');
            return false;
        }
        
        return true;
    }
    
    /**
     * init registry
     */
    protected function _initializeRegistry()
    {
        Syncroton_Registry::setDatabase(Tinebase_Core::getDb());
        Syncroton_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',       Tinebase_Core::getLogger());
        
        if (Tinebase_Core::getUser()->hasRight('Addressbook', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
            Syncroton_Registry::setGALDataClass('ActiveSync_Controller_Contacts');
        }
        if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        }
        if (Tinebase_Core::getUser()->hasRight('Felamimail', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        }
        if (Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN) === true) {
            Syncroton_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
        }
        
        Syncroton_Registry::set(Syncroton_Registry::DEFAULT_POLICY, ActiveSync_Config::getInstance()->get(ActiveSync_Config::DEFAULT_POLICY));
    }
}
