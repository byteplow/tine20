<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */


/**
 * Test class for HumanResources CLI frontend
 */
class HumanResources_Export_MonthlyWTReportTest extends HumanResources_TestCase
{
    /**
     * test employee import
     */
    public function testOdsExport()
    {
        $dailWTRT = new HumanResources_Controller_DailyWTReportTests();
        $employee = $dailWTRT->testCalculateReportsForEmployeeTimesheetsWithStartAndEnd();
        HumanResources_Controller_Account::getInstance()->createMissingAccounts(2018);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $employee->getId()]
        ]);
        $export = new HumanResources_Export_Ods_MonthlyWTReport($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                        'model' => HumanResources_Model_MonthlyWTReport::class,
                        'name' => 'Monthly Report'
                    ]))->getFirstRecord()->getId()
            ]);
        $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(
            Tinebase_Record_Abstract::generateUID()));

        $tempfile = Tinebase_TempFile::getTempPath() . '.ods';
        $export->generate();
        $export->save($tempfile);

        $this->assertGreaterThan(4000, filesize($tempfile));


        $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tempfile);
        $spreadsheet->setActiveSheetIndex(0);
        $this->assertTrue(empty($spreadsheet->getActiveSheet()->getCell('B8')->getValue()));
        $this->assertSame('10:00', $spreadsheet->getActiveSheet()->getCell('B9')->getValue());
        $this->assertSame('10:00', $spreadsheet->getActiveSheet()->getCell('B14')->getValue());
        $this->assertSame('10:00', $spreadsheet->getActiveSheet()->getCell('B15')->getValue());
        $this->assertSame('15:00', $spreadsheet->getActiveSheet()->getCell('B16')->getValue());

        unlink($tempfile);
    }

    public function testHTTPfe()
    {
        $dailWTRT = new HumanResources_Controller_DailyWTReportTests();
        $employee = $dailWTRT->testCalculateReportsForEmployeeTimesheetsWithStartAndEnd();
        HumanResources_Controller_Account::getInstance()->createMissingAccounts(2018);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $employee->getId()]
        ]);
        $data = HumanResources_Controller_MonthlyWTReport::getInstance()->search($filter)->getFirstRecord();

        $httpFE = new HumanResources_Frontend_Http();

        ob_start();
        $httpFE->exportMonthlyWTReportt('', [
            'recordData' => $data->toArray(),
            'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => HumanResources_Model_MonthlyWTReport::class,
                    'name' => 'Monthly Report'
                ]))->getFirstRecord()->getId()
        ]);
        $obData = ob_get_clean();

        $this->assertGreaterThan(4000, strlen($obData));
    }
}
