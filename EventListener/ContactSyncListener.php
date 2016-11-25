<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Event\StepExecutionEvent;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;

use OroCRM\Bundle\DotmailerBundle\Step\ContactSyncStep;

class ContactSyncListener
{
    /**
     * Reset read count for contact sync step
     *
     * @param StepExecutionEvent $event
     */
    public function afterExecutionCompleted(StepExecutionEvent $event)
    {
        /** @var StepExecution $stepExecution */
        $stepExecution = $event->getStepExecution();

        /** @var ExecutionContext $context */
        $context = $stepExecution->getExecutionContext();
        if ($context->get(ContactSyncStep::CONTEXT_STEP_NAME_KEY) == ContactSyncStep::STEP_NAME) {
            $stepExecution->setReadCount(0);
        }
    }
}
