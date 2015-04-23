<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

/**
 * Iterate over all address books contacts
 */
class AddressBookContactIterator extends AbstractIterator
{
    /** @var int */
    protected $batchSize = 100;

    /** @var IResources */
    protected $resources;

    /** @var array|Addressbook[] */
    protected $addressBooks;

    protected $currentAddressBook;

    /** @var ContactIterator */
    protected $contactIterator;

    /**
     * @param IResources    $resources
     * @param AddressBook[] $addressBooks
     */
    public function __construct(IResources $resources, $addressBooks)
    {
        $this->resources = $resources;
        $this->addressBooks = $addressBooks;

        // init first iterator
        $this->initSubIterator();
    }

    /**
     * Initialize sub iterator
     */
    protected function initSubIterator()
    {
        $this->contactIterator = new ContactIterator($this->resources, array_shift($this->addressBooks));
        $this->contactIterator->rewind();
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($select, $skip)
    {
        $count = 0;
        $items = [];

        do {
            $contact = $this->contactIterator->current();
            if (false === $contact && !empty($this->addressBooks)) {
                $this->initSubIterator();
                continue;
            } elseif (false === $contact) {
                // all address books processed
                break;
            }

            $count++;
            $this->contactIterator->next();
        } while ($count < $select);

        return $items;
    }
}
