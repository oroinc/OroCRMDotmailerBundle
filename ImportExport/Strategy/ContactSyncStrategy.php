<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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
     * Fields allowed for update
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);

        if ($entity instanceof Contact) {
            $importedContacts = $this->context->getValue('newImportedItems') ?: [];
            $importedContacts[$entity->getOriginId()] = $entity;
            $this->context->setValue('newImportedItems', $importedContacts);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Contact $entity */
        if ($entity) {
            $addressBook = $this->getAddressBook($entity->getChannel());
            if (!$addressBook) {
                throw new RuntimeException(
                    sprintf('Address book for contact %s not found', $entity->getOriginId())
                );
            }
            $newImportedContacts = $this->context->getValue('newImportedItems');
            $addressBookContact = null;
            /**
             * Fix case if this contact already imported on this batch
             */
            if ($newImportedContacts && isset($newImportedContacts[$entity->getOriginId()])) {
                $entity = $newImportedContacts[$entity->getOriginId()];
                foreach ($entity->getAddressBookContacts() as $addressBookContact) {
                    if ($addressBookContact->getAddressBook()->getId() == $addressBook->getId()) {
                        break;
                    }
                }

            } elseif ($entity->getId()) {
                $addressBookContact = $this->getRepository('OroCRMDotmailerBundle:AddressBookContact')
                    ->findOneBy(['addressBook' => $addressBook, 'contact' => $entity]);
            }

            if (!$entity->getId()) {
                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $entity->setStatus($status);
            }

            if (is_null($addressBookContact)) {
                $addressBookContact = new AddressBookContact();
                $addressBookContact->setAddressBook($addressBook);
                $addressBookContact->setChannel($addressBook->getChannel());

                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $addressBookContact->setStatus($status);
                $entity->addAddressBookContact($addressBookContact);
            }
            $addressBookContact->setMarketingListItemId(
                $this->getMarketingListItemId()
            );
            $addressBookContact->setMarketingListItemClass(
                $addressBook->getMarketingList()->getEntity()
            );
            $addressBookContact->setScheduledForExport(true);
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
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if (!$entity instanceof Contact) {
            return parent::findExistingEntity($entity, $searchContext);
        }

        return $this->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['email' => $entity->getEmail(), 'channel' => $this->getChannel()]);
    }

    /**
     * @param string $className
     * @param array  $allowedFields
     *
     * @return ContactSyncStrategy
     */
    public function setAllowedFields($className, array $allowedFields)
    {
        $this->allowedFields[$className] = $allowedFields;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        if (empty($this->allowedFields[$entityName])) {
            return true;
        }

        return !isset($this->allowedFields[$entityName]);
    }


    /**
     * @param string $enumCode
     * @param string $id
     *
     * @return AbstractEnumValue
     */
    protected function getEnumValue($enumCode, $id)
    {
        $className = ExtendHelper::buildEnumValueClassName($enumCode);
        return $this->getRepository($className)
            ->find($id);
    }
}
