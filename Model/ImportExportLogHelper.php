<?php

namespace OroCRM\Bundle\DotmailerBundle\Model;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

class ImportExportLogHelper
{
    const MEGABYTE = 1048576;
    const MINUTE = 60;

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
     * Return Step execution time in minutes
     *
     * @param StepExecution $stepExecution
     *
     * @return int
     */
    public function getStepExecutionTime(StepExecution $stepExecution)
    {
        /** @var \DateTime $jobStartTime */
        $jobStartTime = $stepExecution->getStartTime();
        $timeSpent = microtime(true) - $jobStartTime->getTimestamp();

        return (int)round($timeSpent / self::MINUTE);
    }
}
