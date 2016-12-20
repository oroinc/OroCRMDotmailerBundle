<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class RemoveDataFieldStrategy extends AbstractImportStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if ($entity instanceof DataField) {
            if (!$entity->getId()) {
                throw new RuntimeException('Datafield Id must be set');
            }

            $existingEntity = $this->registry
                ->getManager()
                ->find('OroDotmailerBundle:DataField', $entity->getId());

            if (!$existingEntity) {
                return null;
            }

            $this->context->incrementDeleteCount();

            return $existingEntity->setForceRemove(true);
        }

        return $entity;
    }
}
