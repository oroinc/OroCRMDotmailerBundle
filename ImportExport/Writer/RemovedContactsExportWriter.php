<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class RemovedContactsExportWriter implements ItemWriterInterface
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
     * @param ManagerRegistry    $registry
     * @param DotmailerTransport $transport
     */
    public function __construct(ManagerRegistry $registry, DotmailerTransport $transport)
    {
        $this->registry = $registry;
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $repository = $this->registry->getRepository('OroCRMDotmailerBundle:Contact');

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

            $qb = $repository->createQueryBuilder('contact');
            $qb->delete()->where($qb->expr()->in('contact.id', $removingItemsIds));
            $qb->getQuery()->execute();

            $removingItemsIds = [];
            $removingItemsIdsCount = 0;
        }

        /**
         * Remove Dotmailer Contacts from Dotmailer
         * Operation is Async in Dotmailer side
         */
        $this->transport->removeContactsFromAddressBook($removingItemsOriginIds, $addressBookOriginId);
    }
}
