<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;

/**
 * Batch job item step for contacts sync.
 */
class ContactSyncStep extends ItemStep
{
    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        parent::doExecute($stepExecution);

        $stepExecution->setReadCount(0);
    }
}
