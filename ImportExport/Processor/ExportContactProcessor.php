<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;

/**
 * Batch job processor for exporting contacts.
 */
class ExportContactProcessor extends ExportProcessor implements StepExecutionAwareProcessor
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($this->stepExecution);
        $this->setImportExportContext($this->context);
    }

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function setContextRegistry($contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }
}
