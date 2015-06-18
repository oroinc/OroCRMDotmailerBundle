<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
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
        $entity = parent::beforeProcessEntity($entity);

        $existingContact = null;

        if ($entity->getContact() instanceof Contact) {
            $entity->getContact()->setChannel($entity->getChannel());
            $existingContact = $this->findExistingContact($entity->getContact());
        }

        if ($existingContact) {
            $entity->setContact($existingContact);
        } else {
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
        $em = $this->strategyHelper->getEntityManager('OroCRMDotmailerBundle:Campaign');
        $cachedCampaigns = $this->context->getValue('cachedCampaignEntities');
        if (!$cachedCampaigns || !isset($cachedCampaigns[$originalValue[ActivityContactIterator::CAMPAIGN_KEY]])) {
            $campaign = $em->getRepository('OroCRMDotmailerBundle:Campaign')
                ->findOneBy(
                    [
                        'channel'  => $channel,
                        'originId' => $originalValue[ActivityContactIterator::CAMPAIGN_KEY]
                    ]
                );
            $cachedCampaigns[$originalValue[ActivityContactIterator::CAMPAIGN_KEY]] = $campaign;

            $this->context->setValue('cachedCampaignEntities', $cachedCampaigns);
        } else {
            $campaign = $this->reattachDetachedEntity(
                $cachedCampaigns[$originalValue[ActivityContactIterator::CAMPAIGN_KEY]]
            );
            $cachedCampaigns[$originalValue[ActivityContactIterator::CAMPAIGN_KEY]] = $campaign;

            $this->context->setValue('cachedCampaignEntities', $cachedCampaigns);
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

    /**
     * {@inheritdoc}
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = []
    ) {
        if (!$entity) {
            return null;
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext);
    }
}
