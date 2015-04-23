<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class ContactIterator extends AbstractIterator
{
    /** @var int */
    protected $batchSize = 100;

    /** @var IResources */
    protected $resources;

    /** @var int */
    protected $addressBookId;

    /**
     * @param IResources  $resources
     * @param AddressBook $addressBookId
     */
    public function __construct(IResources $resources, $addressBookId)
    {
        $this->resources = $resources;
        $this->addressBookId = $addressBookId;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $item = parent::current();
        $item['addressBookId'] = $this->addressBookId;

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($select, $skip)
    {
        $date = null;

        return $this->resources
            ->GetAddressBookContactsModifiedSinceDate(
                $this->addressBookId,
                $date,
                true,
                $select,
                $skip
            )
            ->toArray();
    }
}
