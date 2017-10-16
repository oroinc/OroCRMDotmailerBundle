<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Doctrine\Common\Persistence\ManagerRegistry;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\EntityStateManagerTrait;

class ExportItemStep extends ItemStep
{
    use EntityStateManagerTrait;

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
        $entityStateManager = $this->getEntityStateManager();
        $channel = $this->getChannel($stepExecution);
        $addressBook = $this->getAddressBook($stepExecution, $channel);

        /**
         * Clear old Address Book Export records
         */
        $this->registry
            ->getRepository('OroDotmailerBundle:AddressBookContactsExport')
            ->createQueryBuilder('abContactsExport')
            ->delete()
            ->where('abContactsExport.channel = :channel AND abContactsExport.addressBook = :addressBook')
            ->getQuery()
            ->execute(['channel' => $channel, 'addressBook' => $addressBook]);

        parent::doExecute($stepExecution);

        /** @var AddressBookContactRepository $addressBookContactRepository */
        $addressBookContactRepository = $this->registry->getRepository('OroDotmailerBundle:AddressBookContact');
        $entitiesReadyToReset = $addressBookContactRepository
            ->getAddressBookContactsScheduledToSyncForAddressBook($channel, $addressBook->getId());
        $entityStateManager->resetState($entitiesReadyToReset);
        $entityStateManager->flush();
    }

    /**
     * @param ManagerRegistry $registry
     *
     * @return ExportItemStep
     */
    public function setRegistry(ManagerRegistry $registry = null)
    {
        $this->registry = $registry;

        return $this;
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

    /**
     * @param StepExecution $stepExecution
     *
     * @return Channel
     */
    protected function getChannel(StepExecution $stepExecution)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        return $this->registry
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($context->getOption('channel'));
    }


    /**
     * @param StepExecution $stepExecution
     * @param Channel $channel
     *
     * @return AddressBook|bool
     */
    protected function getAddressBook(StepExecution $stepExecution, Channel $channel)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);

        $addressBooks = $this->registry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->getConnectedAddressBooks(
                $channel,
                $context->getOption('address-book')
            );

        return reset($addressBooks);
    }
}
