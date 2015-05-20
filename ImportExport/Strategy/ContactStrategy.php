<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;

class ContactStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);

        if ($entity instanceof Contact && !$this->databaseHelper->getIdentifier($entity)) {
            $newImportedContacts = $this->context->getValue('newImportedItems') ?: [];
            $newImportedContacts[$entity->getOriginId()] = true;
            $this->context->setValue('newImportedItems', $newImportedContacts);
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

                $addressBookContact->setStatus($entity->getStatus());
            } else {
                throw new RuntimeException(
                    sprintf('Address book for contact %s not found', $entity->getOriginId())
                );
            }

            $newImportedContacts = $this->context->getValue('newImportedItems');
            /**
             * Fix case if this contact already imported on this batch
             */
            if ($newImportedContacts && isset($newImportedContacts[$entity->getOriginId()])) {
                return null;
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        /**
         * Required for match contact after export new one to dotmailer
         */
        if ($entity instanceof Contact && !$existingEntity) {
            if (!$entity->getEmail() || !$entity->getChannel()) {
                throw new RuntimeException("Channel and email required for contact {$entity->getOriginId()}");
            }

            $existingEntity = $this->getRepository('OroCRMDotmailerBundle:Contact')
                ->findOneBy(
                    [
                        'channel' => $entity->getChannel(),
                        'email' => $entity->getEmail()
                    ]
                );
        }

        return $existingEntity;
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBook
     */
    protected function getAddressBook(Channel $channel)
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[ContactIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBook = $this->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(
                [
                    'channel'  => $channel,
                    'originId' => $originalValue[ContactIterator::ADDRESS_BOOK_KEY]
                ]
            );

        return $addressBook;
    }
}
