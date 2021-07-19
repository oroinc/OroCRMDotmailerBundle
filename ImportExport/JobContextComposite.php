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

    /**
     * {@inheritdoc}
     */
    public function addError($message)
    {
        $this->currentStepContext->addError($message);
    }

    /**
     * {@inheritdoc}
     */
    public function addErrors(array $messages)
    {
        $this->currentStepContext->addErrors($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->mergeValuesFromContexts('getErrors');
    }

    /**
     * {@inheritdoc}
     */
    public function addPostponedRow(array $row)
    {
        $this->currentStepContext->addPostponedRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function addPostponedRows(array $rows)
    {
        $this->currentStepContext->addPostponedRows($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostponedRows()
    {
        return $this->mergeValuesFromContexts('getPostponedRows');
    }

    /**
     * {@inheritdoc}
     */
    public function getFailureExceptions()
    {
        return $this->mergeValuesFromContexts('getFailureExceptions');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementReadCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadCount()
    {
        return $this->sumCountsFromContexts('getReadCount');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReadOffset()
    {
        $this->currentStepContext->incrementReadOffset();
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOffset()
    {
        return $this->sumCountsFromContexts('getReadOffset');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAddCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementAddCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddCount()
    {
        return $this->sumCountsFromContexts('getAddCount');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementUpdateCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementUpdateCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateCount()
    {
        return $this->sumCountsFromContexts('getUpdateCount');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementReplaceCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementReplaceCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplaceCount()
    {
        return $this->sumCountsFromContexts('getReplaceCount');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementDeleteCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementDeleteCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteCount()
    {
        return $this->sumCountsFromContexts('getDeleteCount');
    }

    /**
     * {@inheritdoc}
     */
    public function incrementErrorEntriesCount($incrementBy = 1)
    {
        $this->currentStepContext->incrementErrorEntriesCount($incrementBy);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorEntriesCount()
    {
        return $this->sumCountsFromContexts('getErrorEntriesCount');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($name, $value)
    {
        foreach ($this->contexts as $context) {
            $context->setValue($name, null);
        }

        $this->currentStepContext->setValue($name, $value);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->currentStepContext->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        foreach ($this->contexts as $context) {
            if ($context->hasOption($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
