<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;

/**
 * Batch item export processor.
 */
class RemovedExportProcessor implements StepExecutionAwareProcessor
{
    const CURRENT_BATCH_READ_ITEMS = 'currentBatchReadItems';

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if (is_array($item)) {
            $this->context->incrementDeleteCount();

            $items = $this->context->getValue(self::CURRENT_BATCH_READ_ITEMS) ?: [];
            $items[] = $item;

            $this->context->setValue(self::CURRENT_BATCH_READ_ITEMS, $items);
        }

        return $item;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($this->stepExecution);
    }
}
