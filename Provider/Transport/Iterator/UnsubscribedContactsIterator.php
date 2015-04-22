<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class UnsubscribedContactsIterator extends AbstractIterator
{
    /**
     * @var IResources
     */
    protected $resources;

    /**
     * @var array
     */
    protected $addressBooks;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * @param IResources    $resources
     * @param AddressBook[] $addressBooks
     * @param \DateTime     $lastSyncDate
     */
    public function __construct(IResources $resources, array $addressBooks, \DateTime $lastSyncDate )
    {
        $this->resources = $resources;
        $this->addressBooks = $addressBooks;
        $this->lastSyncDate = $lastSyncDate;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        if (!$addressBook = current($this->addressBooks)) {
            return [];
        }

        $contacts = $this->getUnsubscribedContacts($addressBook, $take, $skip);
        while (count($contacts) < $this->batchSize) {
            if (next($this->addressBooks) === false) {
                return $contacts;
            }

            $this->pageNumber = 0;
            $contacts = array_merge($this->getUnsubscribedContacts($addressBook, $take, 0), $contacts);
        }

        return $contacts;
    }

    protected function getUnsubscribedContacts(AddressBook $addressBook, $take, $skip)
    {
        /** @var ApiContactSuppressionList $contacts */
        $contacts = $this->resources
            ->GetAddressBookContactsUnsubscribedSinceDate(
                $addressBook->getId(),
                $this->lastSyncDate,
                $take,
                $skip
            );

        return $contacts->toArray();
    }
}
