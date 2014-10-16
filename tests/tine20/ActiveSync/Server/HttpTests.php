<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for ActiveSync_Server_Http
 * 
 * @package     ActiveSync
 */
class ActiveSync_Server_HttpTests extends ServerTestCase
{
    /**
     * @group ServerTests
     */
    public function testServer()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /Microsoft-Server-ActiveSync?User=abc1234&DeviceId=Appl7R743U8YWH9&DeviceType=iPhone&Cmd=FolderSync HTTP/1.1\r
Host: localhost\r
MS-ASProtocolVersion: 14.1\r
User-Agent: Apple-iPhone/705.18\r
EOS
        );
        
        $credentials = $this->getTestCredentials();
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        $_SERVER['REQUEST_METHOD']            = $request->getMethod();
        $_SERVER['HTTP_MS_ASPROTOCOLVERSION'] = '14.1';
        $_SERVER['HTTP_USER_AGENT']           = 'Apple-iPhone/705.18';
        
        $body = new DOMDocument();
        $body->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        ob_start();
        
        $server = new ActiveSync_Server_Http();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        //TODO needs to be improved (use XML document here)
        $this->assertContains('AwFqAAAHVkwDMQABUgMxAAFOVwM', base64_encode($result),0,30);
    }
}
