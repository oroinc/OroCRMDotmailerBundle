<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\DotmailerBundle\ImportExport\Processor\UpdateEntityFieldsFromContactProcessor;

class UpdateEntityFieldsStep extends ItemStep
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        parent::doExecute($stepExecution);

        /**
         * @var EntityRepository contactRepository
         */
        $contactRepository = $this->registry
            ->getRepository('OroDotmailerBundle:Contact');
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        $contactRepository->resetScheduledForEntityFieldUpdateFlag(
            $context->getValue(UpdateEntityFieldsFromContactProcessor::PROCESSED_CONTACT_IDS)
        );
    }

    /**
     * @param ManagerRegistry $registry
     *
     * @return UpdateEntityFieldsStep
     */
    public function setRegistry(ManagerRegistry $registry = null)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param ContextRegistry $contextRegistry
     *
     * @return UpdateEntityFieldsStep
     */
    public function setContextRegistry(ContextRegistry $contextRegistry = null)
    {
        $this->contextRegistry = $contextRegistry;

        return $this;
    }
}
