<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;

class CampaignSummaryStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var CampaignSummary $entity */
        if ($entity && $entity->getDateSent() && $this->hasEmailCampaign($entity)) {
            $entity->getCampaign()
                ->getEmailCampaign()
                ->setSentAt($entity->getDateSent());
        }
        return parent::afterProcessEntity($entity);
    }

    /**
     * @param CampaignSummary $entity
     * @return bool
     */
    protected function hasEmailCampaign(CampaignSummary $entity)
    {
        return $entity->getCampaign()
            && $entity->getCampaign()->getEmailCampaign();
    }
}
