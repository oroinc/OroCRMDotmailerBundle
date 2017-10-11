<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\IntegrationBundle\Manager\EntityStateManagerTrait;

class ContactSyncStep extends ItemStep
{
    use EntityStateManagerTrait;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        parent::doExecute($stepExecution);

        $stepExecution->setReadCount(0);
    }
}
