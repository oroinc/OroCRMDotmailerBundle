<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

class AddressBookStrategy extends AddOrReplaceStrategy
{
    const EXISTED_ADDRESS_BOOKS_ORIGIN_IDS = 'existedAddressBooksOriginIds';

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
            $existedAddressBooksOriginIds = $this->context->getValue(self::EXISTED_ADDRESS_BOOKS_ORIGIN_IDS) ?: [];
            $existedAddressBooksOriginIds[] = $entity->getOriginId();
            $this->context->setValue(self::EXISTED_ADDRESS_BOOKS_ORIGIN_IDS, $existedAddressBooksOriginIds);
        }

        return $entity;
    }
}
