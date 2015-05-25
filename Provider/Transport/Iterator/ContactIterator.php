<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class ContactIterator extends AbstractIterator
{
    const ADDRESS_BOOK_KEY = 'related_address_book';

    /** @var int */
    protected $batchSize = 1000;

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
     * @param int        $batchSize
     */
    public function __construct(
        IResources $resources,
        $addressBookOriginId,
        \DateTime $dateSince = null,
        $batchSize = 500
    ) {
        $this->resources = $resources;
        $this->dateSince = $dateSince;
        $this->addressBookOriginId = $addressBookOriginId;

        if ($batchSize) {
            $this->batchSize = $batchSize;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($select, $skip)
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

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::ADDRESS_BOOK_KEY] = $this->addressBookOriginId;
        }

        return $items;
    }
}
