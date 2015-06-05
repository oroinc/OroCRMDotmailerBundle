<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;

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

    /**
     * @param ContextRegistry $contextRegistry
     */
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

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($this->stepExecution);
    }
}
