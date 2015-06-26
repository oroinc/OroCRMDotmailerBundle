<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactIterator;

class UnsubscribedContactStrategy extends AbstractImportStrategy
{
    const CACHED_ADDRESS_BOOK = 'cachedAddressBook';

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof AddressBookContact) {
            throw new \RuntimeException(
                sprintf(
                    'Argument must be an instance of "%s", but "%s" is given',
                    'OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->getChannel()) {
            throw new RuntimeException('Channel not found');
        }

        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[UnsubscribedContactIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $channel = $this->getChannel();
        $addressBook = $this->getAddressBook($channel);

        $this->updateAddressBookContact($entity, $channel, $addressBook);

        $contact = $this->getExistingContact($entity, $channel);
        if (!$contact) {
            $contact = $entity->getContact();
            $this->updateContact($contact, $channel);

            $contact->addAddressBookContact($entity);

            /**
             * We need add contact for situation is contact is not imported (not present in any address book)
             * Also we can not check such contacts during export because of Dotmailer export failures report return
             * only contacts unsubscribed from Account.
             */
            $this->registry
                ->getManager()
                ->persist($contact);

            $this->cacheProvider->setCachedItem(AddOrReplaceStrategy::BATCH_ITEMS, $contact->getOriginId(), $contact);
        } elseif ($addressBookContact = $this->getExistingAddressBookContact($addressBook, $contact)) {
            $entity = $addressBookContact
                ->setUnsubscribedDate($entity->getUnsubscribedDate())
                ->setStatus($entity->getStatus());
        } else {
            $contact->addAddressBookContact($entity);
        }

        $this->context->incrementUpdateCount();

        return $entity;
    }

    /**
     * @param AddressBook $addressBook
     * @param Contact     $contact
     *
     * @return AddressBookContact
     */
    protected function getExistingAddressBookContact(AddressBook $addressBook, Contact $contact)
    {
        $addressBookContact = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(['addressBook' => $addressBook, 'contact' => $contact]);

        return $addressBookContact;
    }

    /**
     * @param Contact $contact
     * @param Channel $channel
     */
    protected function updateContact(Contact $contact, Channel $channel)
    {
        $contact->setChannel($channel);
        $contact->setOwner($channel->getOrganization());

        $status = $contact->getEmailType();
        if ($status && $status->getId()) {
            $contact->setStatus($this->getEnumValue('dm_cnt_status', $status->getId()));
        }

        $emailType = $contact->getEmailType();
        if ($emailType && $emailType->getId()) {
            $contact->setEmailType($this->getEnumValue('dm_cnt_email_type', $emailType->getId()));
        }

        $optInType = $contact->getOptInType();
        if ($optInType && $optInType->getId()) {
            $contact->setOptInType($this->getEnumValue('dm_cnt_opt_in_type', $optInType->getId()));
        }
    }

    /**
     * @param AddressBookContact $entity
     * @param Channel            $channel
     * @param AddressBook        $addressBook
     */
    protected function updateAddressBookContact(AddressBookContact $entity, Channel $channel, AddressBook $addressBook)
    {
        $entity->setChannel($channel);
        $entity->setAddressBook($addressBook);
        $status = $this->getEnumValue('dm_cnt_status', $entity->getStatus()->getId());
        $entity->setStatus($status);
    }

    /**
     * @param AddressBookContact $entity
     * @param Channel            $channel
     *
     * @return Contact|null
     */
    protected function getExistingContact(AddressBookContact $entity, Channel $channel)
    {
        $contactOriginId = $entity->getContact()->getOriginId();
        $contact = $this->cacheProvider->getCachedItem(AddOrReplaceStrategy::BATCH_ITEMS, $contactOriginId);
        if (!$contact) {
            $contact = $this->registry
                ->getRepository('OroCRMDotmailerBundle:Contact')
                ->findOneBy(['email' => $entity->getContact()->getEmail(), 'channel' => $channel]);

            $this->cacheProvider->setCachedItem(AddOrReplaceStrategy::BATCH_ITEMS, $contactOriginId, $contact);
        }

        return $contact;
    }

    /**
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[UnsubscribedContactIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBookOriginId = $originalValue[UnsubscribedContactIterator::ADDRESS_BOOK_KEY];
        $addressBook = $this->cacheProvider->getCachedItem(self::CACHED_ADDRESS_BOOK, $addressBookOriginId);
        if (!$addressBook) {
            $addressBook = $this->registry->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->findOneBy(
                    [
                        'channel'  => $this->getChannel(),
                        'originId' => $addressBookOriginId
                    ]
                );

            $this->cacheProvider->setCachedItem(self::CACHED_ADDRESS_BOOK, $addressBookOriginId, $addressBook);
        }

        return $addressBook;
    }
}
