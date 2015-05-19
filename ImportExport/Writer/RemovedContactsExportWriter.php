<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class RemovedContactsExportWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    const BATCH_SIZE = 2000;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DotmailerTransport
     */
    protected $transport;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @param ManagerRegistry    $registry
     * @param DotmailerTransport $transport
     * @param ContextRegistry    $contextRegistry
     */
    public function __construct(
        ManagerRegistry $registry,
        DotmailerTransport $transport,
        ContextRegistry $contextRegistry
    ) {
        $this->registry = $registry;
        $this->transport = $transport;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $repository = $this->registry->getRepository('OroCRMDotmailerBundle:AddressBookContact');

        $addressBookItems = [];
        foreach ($items as $item) {
            $addressBookOriginId = $item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY];
            if (!isset($addressBookItems[$addressBookOriginId])) {
                $addressBookItems[$addressBookOriginId] = [];
            }

            $addressBookItems[$addressBookOriginId][] = $item;
        }
        foreach ($addressBookItems as $addressBookOriginId => $items) {
            $this->removeAddressBookContacts($items, $repository, $addressBookOriginId);
        }
    }

    /**
     * @param array            $items
     * @param EntityRepository $repository
     * @param int              $addressBookOriginId
     */
    protected function removeAddressBookContacts(array $items, EntityRepository $repository, $addressBookOriginId)
    {
        $removingItemsIds = [];
        $removingItemsIdsCount = 0;
        $removingItemsOriginIds = [];
        /**
         * Remove Dotmailer Contacts from DB.
         * Smaller, than step batch used because of "IN" max length
         */
        foreach ($items as $item) {
            $removingItemsIds[] = $item['id'];
            $removingItemsOriginIds[] = $item['originId'];

            if (++$removingItemsIdsCount != static::BATCH_SIZE) {
                continue;
            }

            $this->removeContacts($repository, $removingItemsIds);

            $removingItemsIds = [];
            $removingItemsIdsCount = 0;
        }
        if ($removingItemsIdsCount > 0) {
            $this->removeContacts($repository, $removingItemsIds);
        }

        /**
         * Remove Dotmailer Contacts from Dotmailer
         * Operation is Async in Dotmailer side
         */
        $this->transport->removeContactsFromAddressBook($removingItemsOriginIds, $addressBookOriginId);
    }


    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);

        $this->transport->init($this->getChannel()->getTransport());
    }

    /**
     * @param EntityRepository $repository
     * @param array            $removingItemsIds
     */
    protected function removeContacts(EntityRepository $repository, array $removingItemsIds)
    {
        $qb = $repository->createQueryBuilder('contact');
        $qb->delete()
            ->where($qb->expr()
                ->in('contact.id', $removingItemsIds));
        $qb->getQuery()
            ->execute();
    }
}
