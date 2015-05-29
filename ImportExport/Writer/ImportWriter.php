<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use OroCRM\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class ImportWriter extends PersistentBatchWriter
{
    /**
     * @var ImportExportLogHelper
     */
    protected $logHelper;

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);

        /**
         * clear new imported items list
         */
        $context->setValue('newImportedItems', []);

        parent::write($items);

        gc_collect_cycles();

        $this->logBatchInfo($items, $context);
    }

    /**
     * @param array            $items
     */
    protected function logBatchInfo(array $items)
    {
        $itemsCount = count($items);

        if ($this->stepExecution->getStepName() == 'export') {
            $message = "Batch finished. $itemsCount items prepared for export.";
        } else {
            $message = "Batch finished. $itemsCount items imported.";
        }

        $memoryUsed = $this->logHelper->getMemoryConsumption();
        $stepExecutionTime = $this->logHelper->getStepExecutionTime($this->stepExecution);
        $message .= " Elapsed Time(in minutes): {$stepExecutionTime}. Memory used: $memoryUsed MB .";

        $this->logger->info($message);
    }

    /**
     * @param ImportExportLogHelper $logHelper
     */
    public function setLogHelper(ImportExportLogHelper $logHelper)
    {
        $this->logHelper = $logHelper;
    }
}
