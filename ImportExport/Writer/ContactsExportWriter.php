<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class ContactsExportWriter extends PersistentBatchWriter
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
        $manager = $this->registry->getManager();
        $addressBookItems = [];
        foreach ($items as $item) {
            $addressBookOriginId = $item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY];
            if (!isset($addressBookItems[$addressBookOriginId])) {
                $addressBookItems[$addressBookOriginId] = [];
            }

            $addressBookItems[$addressBookOriginId][] = $item;
        }
        foreach ($addressBookItems as $addressBookOriginId => $items) {
            $this->updateAddressBookContacts($items, $manager, $addressBookOriginId);
        }
    }

    /**
     * @param array         $items
     * @param EntityManager $manager
     * @param int           $addressBookOriginId
     */
    protected function updateAddressBookContacts(array $items, EntityManager $manager, $addressBookOriginId)
    {


        /**
         * Remove Dotmailer Contacts from Dotmailer
         * Operation is Async in Dotmailer side
         */
        $this->transport->removeContactsFromAddressBook($items, $addressBookOriginId);
    }
}
