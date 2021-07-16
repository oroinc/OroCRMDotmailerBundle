<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

class ScheduledForExportContactIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var int
     */
    protected $addressBookId;

    public function __construct(AddressBook $addressBook, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->addressBookId = $addressBook->getId();
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        /** @var EntityManagerInterface $objectManager */
        $objectManager = $this->registry->getManagerForClass(AddressBook::class);

        /** @var AddressBook $addressBook */
        $addressBook = $objectManager->getReference(AddressBook::class, $this->addressBookId);

        $contacts = $this->registry
            ->getRepository('OroDotmailerBundle:Contact')
            ->getScheduledForExportByChannelQB($addressBook)
            ->setFirstResult($skip)
            ->setMaxResults($take)
            ->getQuery()
            /**
             * Call multiple times during import
             * and because of it cache grows larger and script getting out of memory.
             */
            ->useQueryCache(false)
            ->getArrayResult();

        foreach ($contacts as &$contact) {
            $contact[self::ADDRESS_BOOK_KEY] = $addressBook->getOriginId();
        }
        return $contacts;
    }
}
