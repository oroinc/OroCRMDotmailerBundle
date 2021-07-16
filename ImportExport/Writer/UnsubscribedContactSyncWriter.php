<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\DotmailerBundle\ImportExport\Processor\UnsubscribedContactSyncProcessor;

class UnsubscribedContactSyncWriter extends ImportWriter
{
    protected function logBatchInfo(array $items)
    {
        $itemsCount = count($items);

        $message = "$itemsCount Marketing List items Status synchronized.";
        $memoryUsed = $this->logHelper->getMemoryConsumption();
        $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);
        $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

        $this->logger->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);

        /**
         * Clear already read items raw values
         */
        $context->setValue(UnsubscribedContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);

        parent::write($items);
    }
}
