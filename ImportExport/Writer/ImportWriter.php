<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;

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
        parent::write($items);

        $this->logBatchInfo($items);
    }

    protected function logBatchInfo(array $items)
    {
        $itemsCount = count($items);

        if ($this->stepExecution->getStepName() == 'export') {
            $message = "$itemsCount items prepared for export.";
        } else {
            $message = "$itemsCount items imported.";
        }

        $memoryUsed = $this->logHelper->getMemoryConsumption();
        $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);
        $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

        $this->logger->info($message);
    }

    public function setLogHelper(ImportExportLogHelper $logHelper)
    {
        $this->logHelper = $logHelper;
    }
}
