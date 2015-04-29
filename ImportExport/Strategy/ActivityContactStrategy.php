<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;

class ActivityContactStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        /** @var Activity $entity */
        $entity = parent::beforeProcessEntity($entity);

        if ($entity->getContact() instanceof Contact) {
            $entity->getContact()->setChannel($channel);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Activity|null $entity */
        if ($entity) {
            $campaign = $this->getCampaign($entity->getChannel());
            if ($campaign) {
                $entity->setCampaign($campaign);
            } else {
                throw new RuntimeException(
                    sprintf('Campaign for contact %s not found', $entity->getContact()->getOriginId())
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

        if (empty($originalValue[ActivityContactIterator::CAMPAIGN_KEY])) {
            throw new RuntimeException('Campaign id is required');
        }
        $campaign = $this->strategyHelper
            ->getEntityManager('OroCRMDotmailerBundle:Campaign')
            ->getRepository('OroCRMDotmailerBundle:Campaign')
            ->findOneBy(
                [
                    'channel' => $channel,
                    'originId' => $originalValue[ActivityContactIterator::CAMPAIGN_KEY]
                ]
            );

        return $campaign;
    }
}
