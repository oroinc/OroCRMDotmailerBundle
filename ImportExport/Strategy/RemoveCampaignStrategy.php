<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

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
                ->find('OroCRMDotmailerBundle:Campaign', $entity->getId());

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
