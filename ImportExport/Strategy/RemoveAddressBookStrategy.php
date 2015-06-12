<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

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
                ->find('OroCRMDotmailerBundle:AddressBook', $entity->getId());

            if (!$existingEntity) {
                return null;
            }

            $this->context->incrementDeleteCount();

            return $existingEntity;
        }

        return $entity;
    }
}
