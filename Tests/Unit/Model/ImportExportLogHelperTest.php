<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class ImportExportLogHelperTest extends \PHPUnit\Framework\TestCase
{
    private ImportExportLogHelper $target;

    #[\Override]
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

        $stepExecution->expects($this->once())
            ->method('getStartTime')
            ->willReturn($stepExecutionStartDate);

        $minTimeSpent = (int)round(microtime(true)) - $stepExecutionStartDate->getTimestamp();
        $stepExecutionTime = $this->target->getFormattedTimeOfStepExecution($stepExecution);
        $maxTimeSpent = (int)round(microtime(true)) - $stepExecutionStartDate->getTimestamp();

        $this->assertContains(
            $stepExecutionTime,
            array_map(static fn (int $timeSpent) => gmdate('H:i:s', $timeSpent), range($minTimeSpent, $maxTimeSpent))
        );
    }
}
