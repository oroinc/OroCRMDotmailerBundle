<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\DotmailerBundle\EventListener\EntityUpdateListener;
use Oro\Bundle\DotmailerBundle\ImportExport\Processor\UpdateEntityFieldsFromContactProcessor;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

/**
 * Batch job item step.
 * Disables entity update listener during entities update from contacts to avoid re-exporting the same changes
 * back to dotmailer.
 */
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
     * @var EntityUpdateListener
     */
    protected $entityListener;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        if ($this->entityListener) {
            /**
             * disable entity update listener during entities update from contacts
             * to avoid re-exporting the same changes back to dotmailer
             */
            $this->entityListener->setEnabled(false);
        }

        parent::doExecute($stepExecution);

        if ($this->entityListener) {
            $this->entityListener->setEnabled(true);
        }
        /**
         * @var EntityRepository contactRepository
         */
        $contactRepository = $this->registry
            ->getRepository('OroDotmailerBundle:AddressBookContact');
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

    /**
     * @param EntityUpdateListener $listener
     * @return UpdateEntityFieldsStep
     */
    public function setEntityListener(EntityUpdateListener $listener = null)
    {
        $this->entityListener = $listener;

        return $this;
    }
}
