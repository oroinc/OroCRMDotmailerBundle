<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

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
     * @var AddressBook
     */
    protected $addressBook;

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Contact $entity */
        if ($entity) {
            $addressBookContact = null;

            $addressBook = $this->getAddressBook();
            foreach ($entity->getAddressBookContacts() as $abContacts) {
                if ($abContacts->getAddressBook()->getId() == $addressBook->getId()) {
                    $addressBookContact = $abContacts;

                    break;
                }
            }

            if (!$entity->getId()) {
                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $entity->setStatus($status);
            }

            if (is_null($addressBookContact)) {
                $addressBookContact = new AddressBookContact();
                $addressBookContact->setAddressBook($addressBook);
                $addressBookContact->setChannel($this->getChannel());

                $status = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
                $addressBookContact->setStatus($status);
                $this->strategyHelper
                    ->getEntityManager('OroCRMDotmailerBundle:AddressBookContact')
                    ->persist($addressBookContact);

                $entity->addAddressBookContact($addressBookContact);
            }

            if ($entity->getOriginId()) {
                $operationTypeId = $addressBookContact->getId()
                    ? AddressBookContact::EXPORT_UPDATE_CONTACT
                    : AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK;
                $this->updateOperationType($operationTypeId, $addressBookContact);
            } else {
                $this->updateOperationType(AddressBookContact::EXPORT_NEW_CONTACT, $addressBookContact);
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
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[MarketingListItemIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBookOriginId = $originalValue[MarketingListItemIterator::ADDRESS_BOOK_KEY];
        return $this->getAddressBookByOriginId($addressBookOriginId);
    }

    /**
     * {@inheritdoc}
     */
    protected function findProcessedEntity($entity, array $searchContext = [])
    {
        if (!$entity instanceof Contact) {
            throw new RuntimeException('Entity of `\OroCRM\Bundle\DotmailerBundle\Entity\Contact`expected.');
        }

        if (!$entity->getEmail() || !$entity->getChannel()) {
            throw new RuntimeException("Channel and email required for contact {$entity->getOriginId()}");
        }

        /**
         * Fix case if this contact already imported on this batch  but for different address book
         */
        if (!$contact = $this->cacheProvider->getCachedItem(self::BATCH_ITEMS, $entity->getEmail())) {
            $contact = $this->getRepository('OroCRMDotmailerBundle:Contact')
                ->createQueryBuilder('contact')
                ->addSelect('addressBookContacts')
                ->addSelect('addressBook')
                ->where('contact.channel = :channel')
                ->andWhere('contact.email = :email')
                ->leftJoin('contact.addressBookContacts', 'addressBookContacts')
                ->leftJoin('addressBookContacts.addressBook', 'addressBook')
                ->setParameters(['channel' => $entity->getChannel(), 'email' => $entity->getEmail()])
                ->getQuery()
                ->useQueryCache(false)
                ->getOneOrNullResult();

            $this->cacheProvider->setCachedItem(self::BATCH_ITEMS, $entity->getEmail(), $contact ?: $entity);
        }

        return $contact;
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
        $marketingListEntityName = $this->getAddressBook()
            ->getMarketingList()
            ->getEntity();

        if (empty($this->allowedFields[$marketingListEntityName])) {
            return true;
        }

        return !in_array($fieldName, $this->allowedFields[$marketingListEntityName]);
    }

    /**
     * @param string             $operationTypeId
     * @param AddressBookContact $addressBookContact
     */
    protected function updateOperationType($operationTypeId, AddressBookContact $addressBookContact)
    {
        $operationType = $this->getEnumValue('dm_ab_cnt_exp_type', $operationTypeId);
        $addressBookContact->setExportOperationType($operationType);
    }
}
