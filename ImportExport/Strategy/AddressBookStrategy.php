<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

class AddressBookStrategy extends AddOrReplaceStrategy
{
    const EXISTING_ADDRESS_BOOKS_ORIGIN_IDS = 'existingAddressBooksOriginIds';

    /**
     * {@inheritdoc}
     */
    public function beforeProcessEntity($entity)
    {
        $entity = parent::beforeProcessEntity($entity);

        if ($entity instanceof AddressBook) {
            if (!$entity->getOriginId()) {
                throw new RuntimeException("Origin Id required for Address Book '{$entity->getName()}'.");
            }
            $existingAddressBooksOriginIds = $this->context->getValue(self::EXISTING_ADDRESS_BOOKS_ORIGIN_IDS) ?: [];
            $existingAddressBooksOriginIds[] = $entity->getOriginId();
            $this->context->setValue(self::EXISTING_ADDRESS_BOOKS_ORIGIN_IDS, $existingAddressBooksOriginIds);
        }

        return $entity;
    }
}
