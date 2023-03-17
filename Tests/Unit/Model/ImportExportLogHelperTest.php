<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class ImportExportLogHelperTest extends \PHPUnit\Framework\TestCase
{
    private ImportExportLogHelper $target;

    protected function setUp(): void
    {
        $this->target = new ImportExportLogHelper();
    }

    public function testGetMemoryConsumption()
    {
        $this->assertIsInt($this->target->getMemoryConsumption());
    }

    public function testGetStepExecutionTime()
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $stepExecutionStartDate = date_create_from_format(
            'Y-m-d H:i:s',
            '2015-05-03 1:20:34',
            new \DateTimeZone('UTC')
        );

        $stampOfTestBeginning = round(microtime(true)) - $stepExecutionStartDate->getTimestamp();
        $expectedTime = gmdate('H:i:s', $stampOfTestBeginning);

        $stepExecution->expects($this->once())
            ->method('getStartTime')
            ->willReturn($stepExecutionStartDate);

        $stepExecutionTime = $this->target->getFormattedTimeOfStepExecution($stepExecution);
        $this->assertEquals($expectedTime, $stepExecutionTime);
    }
}
