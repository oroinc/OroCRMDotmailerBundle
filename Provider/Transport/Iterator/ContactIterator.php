<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactList;
use DotMailer\Api\Resources\IResources;

class ContactIterator extends OverlapIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /** @var IResources */
    protected $resources;

    /** @var \DateTime|null */
    protected $dateSince;

    /** @var int */
    protected $addressBookOriginId;

    /**
     * @param IResources $resources
     * @param int        $addressBookOriginId
     * @param \DateTime  $dateSince
     */
    public function __construct(IResources $resources, $addressBookOriginId = null, \DateTime $dateSince = null)
    {
        $this->resources = $resources;
        $this->dateSince = $dateSince;
        $this->addressBookOriginId = $addressBookOriginId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        if (is_null($this->addressBookOriginId)) {
            $items = $this->getContacts($take, $skip);
        } else {
            $items = $this->getContactsByAddressBook($take, $skip);
        }

        if (!$items) {
            return [];
        }

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::ADDRESS_BOOK_KEY] = $this->addressBookOriginId;
        }

        return $items;
    }

    /**
     * @param int $select
     * @param int $skip
     *
     * @return ApiContactList
     */
    protected function getContacts($select, $skip)
    {
        if (is_null($this->dateSince)) {
            return $this->resources->GetContacts(true, $select, $skip);
        } else {
            return $this->resources->GetContactsModifiedSinceDate(
                $this->dateSince->format(\DateTime::ISO8601),
                true,
                $select,
                $skip
            );
        }
    }

    /**
     * @param int $select
     * @param int $skip
     *
     * @return ApiContactList
     */
    protected function getContactsByAddressBook($select, $skip)
    {
        if (is_null($this->dateSince)) {
            $items = $this->resources->GetAddressBookContacts($this->addressBookOriginId, true, $select, $skip);
        } else {
            $items = $this->resources->GetAddressBookContactsModifiedSinceDate(
                $this->addressBookOriginId,
                $this->dateSince->format(\DateTime::ISO8601),
                true,
                $select,
                $skip
            );
        }

        return $items;
    }
}
