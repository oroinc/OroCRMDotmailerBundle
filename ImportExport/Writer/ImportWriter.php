<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;

class ImportWriter extends PersistentBatchWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);

        $this->clearTempData($context);

        parent::write($items);

        $this->logBatchInfo($items, $context);
    }

    /**
     * @param array            $items
     * @param ContextInterface $context
     */
    protected function logBatchInfo(array $items, ContextInterface $context)
    {
        $itemsCount = count($items);
        $now = microtime(true);
        $previousBatchFinishTime = $context->getValue('recordingTime');

        if ($this->stepExecution->getStepName() == 'export') {
            $message = "Batch finished. $itemsCount items prepared for export.";
        } else {
            $message = "Batch finished. $itemsCount items imported.";
        }
        if ($previousBatchFinishTime) {
            $spent = round($now - $previousBatchFinishTime);
            $message .= "Time spent: $spent seconds.";
        }
        $memoryUsed = memory_get_usage(true);
        $memoryUsed = $memoryUsed / 1048576;
        $message .= "Memory used: $memoryUsed MB .";

        $this->logger->info($message);

        $context->setValue('recordingTime', $now);
    }

    /**
     * @param ContextInterface $context
     */
    protected function clearTempData(ContextInterface $context)
    {
        /**
         * clear new imported items list
         */
        $context->setValue('newImportedItems', []);
    }
}
