<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class RemoveCampaignStrategy extends AbstractImportStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if ($entity instanceof Campaign) {
            if (!$entity->getId()) {
                throw new RuntimeException('Campaign Id must be set');
            }

            $existingEntity = $this->registry
                ->getManager()
                ->find('OroDotmailerBundle:Campaign', $entity->getId());

            if (!$existingEntity) {
                return null;
            }

            $this->context->incrementDeleteCount();

            return $existingEntity
                ->setDeleted(true);
        }

        return $entity;
    }
}
