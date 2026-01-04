<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Strategy for import ActivityContact entities
 */
class ActivityContactStrategy extends AddOrReplaceStrategy
{
    public const CACHED_CAMPAIGN_ENTITIES = 'cachedCampaignEntities';

    #[\Override]
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
    #[\Override]
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
            $campaign = $this->getRepository(Campaign::class)
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
            ->getEntityManager(Contact::class)
            ->getRepository(Contact::class)
            ->findOneBy(
                [
                    'channel'  => $contact->getChannel(),
                    'originId' => $contact->getOriginId()
                ]
            );

        return $existing;
    }
}
