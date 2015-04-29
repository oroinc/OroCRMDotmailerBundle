<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignSummaryIterator;

class CampaignSummaryStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var CampaignSummary|null $entity */
        if ($entity) {
            $campaign = $this->getCampaign($entity->getChannel());
            if ($campaign) {
                $entity->setCampaign($campaign);
            } else {
                throw new RuntimeException(
                    sprintf('Campaign %s not found', $campaign->getOriginId())
                );
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Integration $channel
     *
     * @return Campaign
     */
    protected function getCampaign(Integration $channel)
    {
        $originalValue = $this->context->getValue('itemData');

        if (empty($originalValue[CampaignSummaryIterator::CAMPAIGN_KEY])) {
            throw new RuntimeException('Campaign id is required');
        }
        $campaign = $this->strategyHelper
            ->getEntityManager('OroCRMDotmailerBundle:Campaign')
            ->getRepository('OroCRMDotmailerBundle:Campaign')
            ->findOneBy(
                [
                    'channel' => $channel,
                    'originId' => $originalValue[CampaignSummaryIterator::CAMPAIGN_KEY]
                ]
            );

        return $campaign;
    }
}
