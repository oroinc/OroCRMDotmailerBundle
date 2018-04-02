<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;

class ActivityStep extends ItemStep
{
    /**
     * @var ProcessLogger
     */
    protected $processLogger;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        if ($this->processLogger) {
            /**
             * disable entity processer logger to avoid memory leaks during activity processes execution
             */
            $this->processLogger->setEnabled(false);
        }

        parent::doExecute($stepExecution);

        if ($this->processLogger) {
            $this->processLogger->setEnabled(true);
        }
    }

    /**
     * @param ProcessLogger $logger
     * @return $this
     */
    public function setProcessLogger(ProcessLogger $logger = null)
    {
        $this->processLogger = $logger;

        return $this;
    }
}
