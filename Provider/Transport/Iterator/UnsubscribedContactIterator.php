<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;

class UnsubscribedContactIterator extends OverlapIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /**
     * @var IResources
     */
    protected $resources;

    /**
     * @var int
     */
    protected $addressBookOriginId;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * @param IResources $resources
     * @param int        $addressBookOriginId
     * @param \DateTime  $lastSyncDate
     */
    public function __construct(IResources $resources, $addressBookOriginId, \DateTime $lastSyncDate)
    {
        $this->resources = $resources;
        $this->addressBookOriginId = $addressBookOriginId;
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
        /** @var ApiContactSuppressionList $contacts */
        $contacts = $this->resources
            ->GetAddressBookContactsUnsubscribedSinceDate(
                $this->addressBookOriginId,
                $this->lastSyncDate->format(\DateTime::ISO8601),
                $take,
                $skip
            );

        $contacts = $contacts->toArray();
        foreach ($contacts as &$contact) {
            $contact[self::ADDRESS_BOOK_KEY] = $this->addressBookOriginId;
        }

        return $contacts;
    }
}
