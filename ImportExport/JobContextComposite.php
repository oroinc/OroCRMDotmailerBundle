<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

/**
 * Job context DTO.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobContextComposite implements ContextInterface
{
    /**
     * Required for support put method
     *
     * @var ContextInterface
     */
    protected $currentStepContext;

    /**
     * @var ContextInterface[]
     */
    protected $contexts;

    public function __construct(StepExecution $stepExecution, ContextRegistry $contextRegistry)
    {
        $stepExecutions = $stepExecution
            ->getJobExecution()
            ->getStepExecutions();

        $this->currentStepContext = $contextRegistry->getByStepExecution($stepExecution);

        $contexts = [];
        foreach ($stepExecutions as $stepExecution) {
            $contexts[] = $contextRegistry->getByStepExecution($stepExecution);
        }

        $this->contexts = $contexts;
    }

    #[\Override]
    public function addError($message)
    {
        $this->currentStepContext->addError($message);
    }

    #[\Override]
    public function addErrors(array $messages)
    {
        $this->currentStepContext->addErrors($messages);
    }

    #[\Override]
    public function getErrors()
    {
        return $this->mergeValuesFromContexts('getErrors');
    }

    #[\Override]
    public function addPostponedRow(array $row)
    {
        $this->currentStepContext->addPostponedRow($row);
    }

    #[\Override]
    public function addPostponedRows(array $rows)
    {
        $this->currentStepContext->addPostponedRows($rows);
    }

    #[\Override]
    public function getPostponedRows()
    {
        return $this->mergeValuesFromContexts('getPostponedRows');
    }

    #[\Override]
    public function getFailureExceptions()
    {
        return $this->mergeValuesFromContexts('getFailureExceptions');
    }

    #[\Override]
    public function incrementReadCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementReadCount($incrementBy);
    }

    #[\Override]
    public function getReadCount()
    {
        return $this->sumCountsFromContexts('getReadCount');
    }

    #[\Override]
    public function incrementReadOffset()
    {
        $this->currentStepContext->incrementReadOffset();
    }

    #[\Override]
    public function getReadOffset()
    {
        return $this->sumCountsFromContexts('getReadOffset');
    }

    #[\Override]
    public function incrementAddCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementAddCount($incrementBy);
    }

    #[\Override]
    public function getAddCount()
    {
        return $this->sumCountsFromContexts('getAddCount');
    }

    #[\Override]
    public function incrementUpdateCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementUpdateCount($incrementBy);
    }

    #[\Override]
    public function getUpdateCount()
    {
        return $this->sumCountsFromContexts('getUpdateCount');
    }

    #[\Override]
    public function incrementReplaceCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementReplaceCount($incrementBy);
    }

    #[\Override]
    public function getReplaceCount()
    {
        return $this->sumCountsFromContexts('getReplaceCount');
    }

    #[\Override]
    public function incrementDeleteCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementDeleteCount($incrementBy);
    }

    #[\Override]
    public function getDeleteCount()
    {
        return $this->sumCountsFromContexts('getDeleteCount');
    }

    #[\Override]
    public function incrementErrorEntriesCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementErrorEntriesCount($incrementBy);
    }

    #[\Override]
    public function getErrorEntriesCount()
    {
        return $this->sumCountsFromContexts('getErrorEntriesCount');
    }

    #[\Override]
    public function setValue($name, $value)
    {
        foreach ($this->contexts as $context) {
            $context->setValue($name, null);
        }

        $this->currentStepContext->setValue($name, $value);
    }

    #[\Override]
    public function getValue($name)
    {
        $value = null;
        foreach ($this->contexts as $context) {
            $contextValue = $context->getValue($name);
            if (is_null($contextValue)) {
                continue;
            }

            if (is_array($contextValue)) {
                $value = is_array($value) ? array_merge($value, $contextValue) : $contextValue;
            } else {
                return $contextValue;
            }
        }

        return $value;
    }

    #[\Override]
    public function getConfiguration()
    {
        return $this->currentStepContext->getConfiguration();
    }

    #[\Override]
    public function hasOption($name)
    {
        foreach ($this->contexts as $context) {
            if ($context->hasOption($name)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function getOption($name, $default = null)
    {
        $option = $default;
        foreach ($this->contexts as $context) {
            $contextOption = $context->getOption($name);
            if (is_null($contextOption)) {
                continue;
            }

            if (is_array($contextOption)) {
                $option = is_array($option) ? array_merge($option, $contextOption) : $contextOption;
            } else {
                return $contextOption;
            }
        }

        return $option;
    }

    #[\Override]
    public function removeOption($name)
    {
        foreach ($this->contexts as $context) {
            $context->removeOption($name);
        }
    }

    /**
     * @param string $contextMethod
     * @return array
     */
    private function mergeValuesFromContexts($contextMethod)
    {
        $values = [];
        foreach ($this->contexts as $context) {
            $values = array_merge($values, $context->$contextMethod());
        }

        return $values;
    }

    /**
     * @param string $contextMethod
     * @return int
     */
    private function sumCountsFromContexts($contextMethod)
    {
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->$contextMethod();
        }

        return $count;
    }
}
