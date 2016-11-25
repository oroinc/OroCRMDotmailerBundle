<?php

namespace OroCRM\Bundle\DotmailerBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class ContactSyncStep extends ItemStep
{
    const STEP_NAME             = 'orocrm_dotmailer_importexport_contact_sync';
    const CONTEXT_STEP_NAME_KEY = 'stepName';

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $context->setValue(self::CONTEXT_STEP_NAME_KEY, self::STEP_NAME);
    
        parent::doExecute($stepExecution);
    }
    
    /**
     * @param ContextRegistry $contextRegistry
     *
     * @return ExportItemStep
     */
    public function setContextRegistry(ContextRegistry $contextRegistry = null)
    {
        $this->contextRegistry = $contextRegistry;

        return $this;
    }
}
