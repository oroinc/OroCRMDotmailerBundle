<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class ScheduledForExportContactIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * @param AddressBook     $addressBook
     * @param ManagerRegistry $registry
     */
    public function __construct(AddressBook $addressBook, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->addressBook = $addressBook;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        $contacts = $this->registry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->getScheduledForExportByChannelQB($this->addressBook)
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
            $contact[self::ADDRESS_BOOK_KEY] = $this->addressBook->getOriginId();
        }
        return $contacts;
    }
}
