<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Controller_Sieve
 */
class Felamimail_Controller_SieveTest extends Felamimail_TestCase
{
    protected $_oldXpropsSetting = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'container', 'is_deleted = 1');
    }

    public function testAdbMailinglistPutSieveRule()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist();
        self::assertInstanceOf(Addressbook_Model_List::class, $mailinglist);
        $result = Felamimail_Sieve_AdbList::setScriptForList($mailinglist);
        self::assertTrue($result);
    }

    public function testAdbMailinglistSieveRuleForward()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist();

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);

        self::assertNotNull($script);
        self::assertStringContainsString('require ["envelope","copy","reject"];', $script->getSieve());
        $domains = Tinebase_EmailUser::getAllowedDomains();
        self::assertStringContainsString('if address :is :domain "from" ' . json_encode($domains) . ' {
redirect :copy "' . Tinebase_Core::getUser()->accountEmailAddress . '";
} else { reject', $script->getSieve());

        // TODO make it work (our sieve testsetup is not ready for this)
        return true;
        // write mail to list & check if user receives mail
        $this->_sendAndAssertMail(array($mailinglist->email));
    }

    public function testAdbMailinglistSieveRuleCopy()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY
        ]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertNotNull($script);
        $domains = Tinebase_EmailUser::getAllowedDomains();
        self::assertStringContainsString('if address :is :domain "from" ' . json_encode($domains) . ' {
redirect :copy "' . Tinebase_Core::getUser()->accountEmailAddress . '";
} else { reject "', $script->getSieve());

        // TODO check sieve script functionality
    }

    public function testAdbMailinglistSieveRuleForwardExternal()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL
        ]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertNotNull($script);
        self::assertStringNotContainsString('if address :is :domain "from" ["' . TestServer::getPrimaryMailDomain() . '"]', $script->getSieve());

        // TODO check sieve script functionality
    }

    public function testAdbMailinglistSieveRuleForwardOnlyMembers()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS
        ]);

        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $raii = new Tinebase_RAII(function() use($sclever) {
            $sclever->visibility = Tinebase_Model_User::VISIBILITY_DISPLAYED;
            Tinebase_User::getInstance()->updateUserInSqlBackend($sclever);
        });
        $sclever->visibility = Tinebase_Model_User::VISIBILITY_HIDDEN;
        Tinebase_User::getInstance()->updateUserInSqlBackend($sclever);

        Addressbook_Controller_List::getInstance()->addListMember($mailinglist, [$sclever->contact_id]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertStringContainsString('if address :is :all "from" ["' . $this->_originalTestUser->accountEmailAddress . '"]', $script->getSieve());
        self::assertStringContainsString('reject "Your email has been rejected"', $script->getSieve());

        // TODO check sieve script functionality

        // for unused variable check
        unset($raii);
    }

    public function testSetSieveOfSharedAccount()
    {
        $account = $this->_createSharedAccount();
        $vacationData = Felamimail_Frontend_JsonTest::getVacationData($account);
        $script = Felamimail_Controller_Sieve::getInstance()->setSieveScript($account->getId(), new Felamimail_Model_Sieve_Vacation($vacationData));
        $vacation = Felamimail_Controller_Sieve::getInstance()->getVacation($account->getId());
        
        self::assertEquals(true, $vacation->enabled);
    }
}
