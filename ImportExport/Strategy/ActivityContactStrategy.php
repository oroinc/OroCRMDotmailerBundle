<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;

class ActivityContactStrategy extends AddOrReplaceStrategy
{
    const CACHED_CAMPAIGN_ENTITIES = 'cachedCampaignEntities';

    /**
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        $entity = parent::beforeProcessEntity($entity);

        $existingContact = null;

        if ($entity->getContact() instanceof Contact) {
            $entity->getContact()->setChannel($entity->getChannel());
            $existingContact = $this->findExistingContact($entity->getContact());
        }

        if ($existingContact) {
            $entity->setContact($existingContact);
        } else {
            $this->logger->critical(
                sprintf(
                    'Contact \'%s\', which is associated with Activity not found.',
                    $entity->getContact()->getOriginId()
                )
            );
            return null;
        }

        $campaign = $this->getCampaign($entity->getChannel());
        if ($campaign) {
            $entity->setCampaign($campaign);
        } else {
            throw new RuntimeException(
                sprintf('Campaign for contact %s not found', $entity->getContact()->getOriginId())
            );
        }

        return $entity;
    }

    /**
     * @param Activity $entity
     *
     * @return string
     */
    protected function getCurrentBatchItemsCacheKey($entity)
    {
        return "{$entity->getCampaign()->getId()}_{$entity->getContact()->getId()}";
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

        $campaignOriginId = $originalValue[ActivityContactIterator::CAMPAIGN_KEY];

        $campaign = $this->cacheProvider->getCachedItem(self::CACHED_CAMPAIGN_ENTITIES, $campaignOriginId);
        if (!$campaign) {
            $campaign = $this->getRepository('OroCRMDotmailerBundle:Campaign')
                ->createQueryBuilder('dmCampaign')
                ->addSelect('addressBooks')
                ->addSelect('emailCampaign')
                ->addSelect('marketingList')
                ->where('dmCampaign.channel =:channel')
                ->andWhere('dmCampaign.originId =:originId')
                ->leftJoin('dmCampaign.addressBooks', 'addressBooks')
                ->leftJoin('addressBooks.marketingList', 'marketingList')
                ->leftJoin('dmCampaign.emailCampaign', 'emailCampaign')
                ->setParameters([
                    'channel'  => $channel,
                    'originId' => $campaignOriginId
                ])
                ->getQuery()
                ->useQueryCache(false)
                ->getOneOrNullResult();

            $this->cacheProvider->setCachedItem(self::CACHED_CAMPAIGN_ENTITIES, $campaignOriginId, $campaign);
        }

        return $campaign;
    }

    /**
     * @param Contact $contact
     *
     * @return Contact
     */
    protected function findExistingContact(Contact $contact)
    {
        $existing = $this->strategyHelper
            ->getEntityManager('OroCRMDotmailerBundle:Contact')
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(
                [
                    'channel'  => $contact->getChannel(),
                    'originId' => $contact->getOriginId()
                ]
            );

        return $existing;
    }
}
