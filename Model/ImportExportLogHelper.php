<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Oro\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Contains helper methods for logging during import/export.
 */
class ImportExportLogHelper
{
    const MEGABYTE = 1048576;

    /**
     * Return Memory Consumption in MB
     *
     * @return int
     */
    public function getMemoryConsumption()
    {
        $memoryConsumption = memory_get_usage(true) / self::MEGABYTE;
        return (int)round($memoryConsumption);
    }

    /**
     * Return formatted Step execution time
     *
     * @param StepExecution $stepExecution
     *
     * @return string
     */
    public function getFormattedTimeOfStepExecution(StepExecution $stepExecution)
    {
        /** @var \DateTime $jobStartTime */
        $jobStartTime = $stepExecution->getStartTime();
        $timeSpent = round(microtime(true)) - $jobStartTime->getTimestamp();

        return gmdate('H:i:s', $timeSpent);
    }
}
