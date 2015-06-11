<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\UnsubscribedContactSyncProcessor;

class UnsubscribedContactSyncWriter extends ImportWriter
{
    /**
     * @param array            $items
     */
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
    protected function clearTempValues(ContextInterface $context)
    {
        parent::clearTempValues($context);
        /**
         * Clear already read items raw values
         */
        $context->setValue(UnsubscribedContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);
    }
}
