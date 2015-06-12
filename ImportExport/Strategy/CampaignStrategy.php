<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;

class CampaignStrategy extends AddOrReplaceStrategy
{
    const EXISTING_CAMPAIGNS_ORIGIN_IDS = 'existingCampaignsOriginIds';

    /**
     * {@inheritdoc}
     */
    public function beforeProcessEntity($entity)
    {
        $entity = parent::beforeProcessEntity($entity);

        if ($entity instanceof Campaign) {
            if (!$entity->getOriginId()) {
                throw new RuntimeException("Origin Id required for Campaign '{$entity->getName()}'.");
            }
            $existingCampaignsOriginIds = $this->context->getValue(self::EXISTING_CAMPAIGNS_ORIGIN_IDS) ?: [];
            $existingCampaignsOriginIds[] = $entity->getOriginId();
            $this->context->setValue(self::EXISTING_CAMPAIGNS_ORIGIN_IDS, $existingCampaignsOriginIds);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Campaign $entity */
        if ($entity) {
            $batchItems = $this->context->getValue(self::BATCH_ITEMS);
            /**
             * Fix case if this campaign already imported on this batch
             */
            if ($batchItems && !$entity->getId() && isset($batchItems[$entity->getOriginId()])) {
                $entity = $batchItems[$entity->getOriginId()];
            }

            $addressBook = $this->getAddressBook($entity->getChannel());
            if ($addressBook) {
                $entity->addAddressBook($addressBook);
            } else {
                throw new RuntimeException(
                    sprintf('Address book for campaign %s not found', $entity->getOriginId())
                );
            }
        }
        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Integration $channel
     *
     * @return AddressBook
     */
    protected function getAddressBook(Integration $channel)
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[CampaignIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBook = $this->strategyHelper
            ->getEntityManager('OroCRMDotmailerBundle:AddressBook')
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(
                [
                    'channel'  => $channel,
                    'originId' => $originalValue[CampaignIterator::ADDRESS_BOOK_KEY]
                ]
            );

        return $addressBook;
    }
}
