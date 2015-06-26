<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

class RemoveCampaignWriter extends ImportWriter
{
    /**
     * @param array            $items
     */
    protected function logBatchInfo(array $items)
    {
        $itemsCount = count($items);

        $memoryUsed = $this->logHelper->getMemoryConsumption();
        $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);

        $message = "$itemsCount items removed. Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";
        $this->logger->info($message);
    }
}
