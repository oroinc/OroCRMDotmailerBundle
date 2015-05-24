<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class ContactSyncStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Contact $entity */
        if ($entity) {
            $addressBook = $this->getAddressBook($entity->getChannel());
            if ($addressBook) {
                $addressBookContact = null;
                if ($entity->getId()) {
                    $addressBookContact = $this->getRepository('OroCRMDotmailerBundle:AddressBookContact')
                        ->findOneBy(['addressBook' => $addressBook, 'contact' => $entity]);
                }

                if (is_null($addressBookContact)) {
                    $addressBookContact = new AddressBookContact();
                    $addressBookContact->setAddressBook($addressBook);
                    $addressBookContact->setChannel($addressBook->getChannel());
                    $entity->addAddressBookContact($addressBookContact);
                }
                $addressBookContact->setMarketingListItemId(
                    $this->getMarketingListItemId()
                );
                $addressBookContact->setMarketingListItemClass(
                    $addressBook->getMarketingList()->getEntity()
                );
                $addressBookContact->setScheduledForExport(true);
            } else {
                throw new RuntimeException(
                    sprintf('Address book for contact %s not found', $entity->getOriginId())
                );
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @return int
     */
    protected function getMarketingListItemId()
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[MarketingListItemsQueryBuilderProvider::MARKETING_LIST_ITEM_ID])) {
            throw new RuntimeException('Marketing list item id required');
        }
        return $originalValue[MarketingListItemsQueryBuilderProvider::MARKETING_LIST_ITEM_ID];
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBook
     */
    protected function getAddressBook(Channel $channel)
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[MarketingListItemIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBook = $this->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(
                [
                    'channel'  => $channel,
                    'originId' => $originalValue[MarketingListItemIterator::ADDRESS_BOOK_KEY]
                ]
            );

        return $addressBook;
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = array())
    {
        if (!$entity instanceof Contact) {
            return parent::findExistingEntity($entity, $searchContext);
        }

        return $this->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['email' => $entity->getEmail(), 'channel' => $this->getChannel()]);
    }

    /**
     * {@inheritdoc}
     */
    protected function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        if ($fieldName == 'channel' || $fieldName == 'originId') {
            return true;
        }

        return parent::isFieldExcluded($entityName, $fieldName, $itemData);
    }
}
