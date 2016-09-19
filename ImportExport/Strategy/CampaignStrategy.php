<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;

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
            $entity->setDeleted(false);
            $addressBook = $this->getAddressBook();
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
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[CampaignIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBookOriginId = $originalValue[CampaignIterator::ADDRESS_BOOK_KEY];
        return $this->getAddressBookByOriginId($addressBookOriginId);
    }
}
