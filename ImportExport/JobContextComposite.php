<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

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

    /**
     * @param StepExecution   $stepExecution
     * @param ContextRegistry $contextRegistry
     */
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
        $errors = [];
        foreach ($this->contexts as $context) {
            $errors = array_merge($errors, $context->getErrors());
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getFailureExceptions()
    {
        $exceptions = [];
        foreach ($this->contexts as $context) {
            $exceptions = array_merge($exceptions, $context->getFailureExceptions());
        }

        return $exceptions;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getReadCount();
        }

        return $count;
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
        $offset = 0;
        foreach ($this->contexts as $context) {
            $offset += $context->getReadOffset();
        }

        return $offset;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getAddCount();
        }

        return $count;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getUpdateCount();
        }

        return $count;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getReplaceCount();
        }

        return $count;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getDeleteCount();
        }

        return $count;
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
        $count = 0;
        foreach ($this->contexts as $context) {
            $count += $context->getErrorEntriesCount();
        }

        return $count;
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
}
