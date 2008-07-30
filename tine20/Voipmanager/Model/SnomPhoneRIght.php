<?php
/**
 * class to hold phone rights data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold phone rights data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SnomPhoneRight extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'account_id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'account_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'read_right'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'write_right' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'dial_right'  => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );    
}