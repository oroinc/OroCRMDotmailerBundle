<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Model;

use OroCRM\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class ImportExportLogHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImportExportLogHelper
     */
    protected $target;

    protected function setUp()
    {
        $this->target = new ImportExportLogHelper();
    }

    public function testGetMemoryConsumption()
    {
        $this->assertInternalType('int', $this->target->getMemoryConsumption());
    }

    public function testGetStepExecutionTime()
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $stepExecutionStartDate = date_create_from_format(
            'Y-m-d H:i:s',
            '2015-05-03 1:20:34',
            new \DateTimeZone('UTC')
        );

        $stampOfTestBeginning = round(microtime(true)) - $stepExecutionStartDate->getTimestamp();
        $expectedTime = gmdate('H:i:s', $stampOfTestBeginning);

        $stepExecution->expects($this->once())
            ->method('getStartTime')
            ->will($this->returnValue($stepExecutionStartDate));

        $stepExecutionTime = $this->target->getFormattedTimeOfStepExecution($stepExecution);
        $this->assertEquals($expectedTime, $stepExecutionTime);
    }
}
