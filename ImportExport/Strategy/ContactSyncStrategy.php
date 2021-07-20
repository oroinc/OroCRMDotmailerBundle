<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class ContactSyncStrategy extends AddOrReplaceStrategy
{
    /**
     * Custom fields allowed for update
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * Internal fields which are always allowed
     *
     * @var array
     */
    protected $alwaysAllowedFields = ['dataFields'];

    /**
     * @var AddressBook
     */
    protected $addressBook;

    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var bool  */
    protected $scheduleForExport = true;

    public function setMappingProvider(MappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

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
                    ->getEntityManager('OroDotmailerBundle:AddressBookContact')
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
            $this->processAbContactStateFlags($addressBookContact);
        }

        return parent::afterProcessEntity($entity);
    }

    protected function processAbContactStateFlags(AddressBookContact $addressBookContact)
    {
        if ($this->scheduleForExport ||
            $addressBookContact->getExportOperationType()->getId() !== AddressBookContact::EXPORT_UPDATE_CONTACT) {
            $addressBookContact->setScheduledForExport(true);
        }
        //reset export flag
        $this->scheduleForExport = true;
        //reset entity update flag
        $addressBookContact->setEntityUpdated(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = []
    ) {
        /** @var Contact $existingEntity */
        /** @var Contact $entity */
        $diff = array_diff_assoc((array) $entity->getDataFields(), (array) $existingEntity->getDataFields());
        if ($diff) {
            $diff = $this->handleFieldsSyncPriority($existingEntity, $diff);
            //update modified datafield values
            $dataFields = array_merge($existingEntity->getDataFields(), $diff);
            $entity->setDataFields($dataFields);
        }
        //if no datafields were changed, no need to export contact
        $this->scheduleForExport = $diff ? true : false;

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }

    /**
     * Look through other address book contacts and, if there is a marketing list item
     * with a higher priority set for marketing list class, remove the field from the update list,
     * because we need to keep values from entities with the highest sync priority
     *
     * @param Contact $existingEntity
     * @param array $changedFields
     * @return array
     */
    protected function handleFieldsSyncPriority($existingEntity, $changedFields)
    {
        if (!$this->mappingProvider) {
            throw new RuntimeException('Mapping provider must be set');
        }
        $priorities = $this->mappingProvider->getDataFieldMappingBySyncPriority($this->getChannel());
        foreach ($changedFields as $name => $value) {
            $currentPriority = isset($priorities[$name][$this->getMarketingListEntityName()]) ?
                $priorities[$name][$this->getMarketingListEntityName()] : 0;
            foreach ($existingEntity->getAddressBookContacts() as $abContact) {
                $class = $abContact->getMarketingListItemClass();
                if (isset($priorities[$name][$class]) && $priorities[$name][$class] > $currentPriority) {
                    unset($changedFields[$name]);
                    break;
                }
            }
        }

        return $changedFields;
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
            throw new RuntimeException('Entity of `\Oro\Bundle\DotmailerBundle\Entity\Contact`expected.');
        }

        if (!$entity->getEmail() || !$entity->getChannel()) {
            throw new RuntimeException("Channel and email required for contact {$entity->getOriginId()}");
        }

        /**
         * Fix case if this contact already imported on this batch  but for different address book
         */
        if (!$contact = $this->cacheProvider->getCachedItem(self::BATCH_ITEMS, $entity->getEmail())) {
            $contact = $this->getRepository('OroDotmailerBundle:Contact')
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
        if (in_array($fieldName, $this->alwaysAllowedFields, true)) {
            return false;
        }
        $marketingListEntityName = $this->getMarketingListEntityName();

        if (empty($this->allowedFields[$marketingListEntityName])) {
            return true;
        }

        return !in_array($fieldName, $this->allowedFields[$marketingListEntityName]);
    }

    /**
     * @return string
     */
    protected function getMarketingListEntityName()
    {
        return $this->getAddressBook()->getMarketingList()->getEntity();
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

    /**
     * {@inheritdoc}
     */
    protected function assertEnvironment($entity)
    {
        if (!$this->mappingProvider) {
            throw new RuntimeException('Mapping provider must be set');
        }

        parent::assertEnvironment($entity);
    }
}
