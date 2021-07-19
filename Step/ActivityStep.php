<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;

/**
 * Batch job item step for activity sync.
 */
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
     * @param ProcessLogger|null $logger
     * @return self
     */
    public function setProcessLogger(ProcessLogger $logger = null)
    {
        $this->processLogger = $logger;

        return $this;
    }
}
