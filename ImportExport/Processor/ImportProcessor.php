<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor as BaseImportProcessor;

/**
 * Base batch job processor for importing.
 */
class ImportProcessor extends BaseImportProcessor implements StepExecutionAwareInterface
{
    const NOT_PROCESSED_ITEMS = 'notProcessedItems';

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);

        $this->setImportExportContext($context);
    }
}
