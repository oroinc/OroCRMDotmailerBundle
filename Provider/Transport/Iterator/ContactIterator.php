<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactList;
use DotMailer\Api\Resources\IResources;

class ContactIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';
    const OVERLAP = 100;

    /** @var int */
    protected $batchSize = 900;

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
    protected function getItems($select, $skip)
    {
        /**
         * overlap necessary because of during import some contacts can be unsubscribed,
         * and in this case we can miss some entities. Also we can not iterate from the end because of api
         * restrictions
         */
        if ($skip > self::OVERLAP) {
            $skip -= self::OVERLAP;
            $select += self::OVERLAP;
        }

        if (is_null($this->addressBookOriginId)) {
            $items = $this->resources->GetContactsModifiedSinceDate($this->dateSince->format(\DateTime::ISO8601), true);
        } else {
            $items = $this->getContactsByAddressBook($select, $skip);
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
