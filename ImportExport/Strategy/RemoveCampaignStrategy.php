<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

/**
 * Strategy for import Campaign entities
 */
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
                ->find(Campaign::class, $entity->getId());

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
