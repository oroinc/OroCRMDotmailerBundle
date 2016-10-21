<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class RemoveAddressBookStrategy extends AbstractImportStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if ($entity instanceof AddressBook) {
            if (!$entity->getId()) {
                throw new RuntimeException('Address book Id must be set');
            }

            $existingEntity = $this->registry
                ->getManager()
                ->find('OroDotmailerBundle:AddressBook', $entity->getId());

            if (!$existingEntity) {
                return null;
            }

            $this->context->incrementDeleteCount();

            return $existingEntity;
        }

        return $entity;
    }
}
