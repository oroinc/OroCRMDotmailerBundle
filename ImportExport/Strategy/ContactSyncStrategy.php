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
     * Fields allowed for update
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * @var Channel|null
     */
    protected $channel = null;

    /**
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * {@inheritdoc}
     */
    public function beforeProcessEntity($entity)
    {
        $this->channel = $this->getChannel();
        $this->addressBook = $this->getAddressBook($this->channel);
        if (!$this->addressBook) {
            throw new RuntimeException(
                sprintf('Address book for contact %s not found', $entity->getOriginId())
            );
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessAndValidationEntity($entity)
    {
        if ($entity instanceof Contact) {
            $this->cacheProvider->setCachedItem(self::BATCH_ITEMS, $entity->getEmail(), $entity);
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
            $batchItems = $this->context->getValue(self::BATCH_ITEMS);
            $addressBookContact = null;

            /**
             * Fix case if this contact already imported on this batch  but for different address book
             */
            if ($batchItems && isset($batchItems[$entity->getEmail()])) {
                $entity = $batchItems[$entity->getEmail()];
                foreach ($entity->getAddressBookContacts() as $abContacts) {
                    if ($abContacts->getAddressBook()->getId() == $this->addressBook->getId()) {
                        $addressBookContact = $abContacts;

                        break;
                    }
                }

            } elseif ($entity->getId()) {
                $addressBookContact = $this->getRepository('OroCRMDotmailerBundle:AddressBookContact')
                    ->findOneBy(['addressBook' => $this->addressBook, 'contact' => $entity]);
            }

            if (!$entity->getId()) {
                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $entity->setStatus($status);
            }

            if (is_null($addressBookContact)) {
                $addressBookContact = new AddressBookContact();
                $addressBookContact->setAddressBook($this->addressBook);
                $addressBookContact->setChannel($this->channel);

                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $addressBookContact->setStatus($status);
                $entity->addAddressBookContact($addressBookContact);
            }
            $addressBookContact->setMarketingListItemId(
                $this->getMarketingListItemId()
            );
            $addressBookContact->setMarketingListItemClass(
                $this->addressBook->getMarketingList()->getEntity()
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

        $addressBookOriginId = $originalValue[MarketingListItemIterator::ADDRESS_BOOK_KEY];
        if ($addressBook = $this->cacheProvider->getCachedItem('addressBook', $addressBookOriginId))
        $addressBook = $this->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(
                [
                    'channel'  => $channel,
                    'originId' => $addressBookOriginId
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
            ->findOneBy(['email' => $entity->getEmail(), 'channel' => $this->channel]);
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
        $marketingListEntityName = $this->addressBook
            ->getMarketingList()
            ->getEntity();

        if (empty($this->allowedFields[$marketingListEntityName])) {
            return true;
        }

        return !in_array($fieldName, $this->allowedFields[$marketingListEntityName]);
    }
}
