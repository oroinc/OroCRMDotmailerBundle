<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;

class ContactStrategy extends AddOrReplaceStrategy
{
    const CACHED_ADDRESS_BOOK_ENTITIES = 'cachedAddressBookEntities';

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        /** @var Contact $entity */
        if ($entity) {
            if ($addressBook = $this->getAddressBook()) {
                /**
                 * Can Contains duplicates of contact from the same address book because of
                 * overlap
                 */
                $addressBookContact = null;
                foreach ($entity->getAddressBookContacts() as $existingAddressBookContact) {
                    $isSameAddressBook = $addressBook->getId() == $existingAddressBookContact
                            ->getAddressBook()
                            ->getId();
                    if ($isSameAddressBook) {
                        $addressBookContact = $existingAddressBookContact;

                        break;
                    }
                }

                if (is_null($addressBookContact)) {
                    $addressBookContact = new AddressBookContact();
                    $addressBookContact->setAddressBook($addressBook);
                    $addressBookContact->setChannel($addressBook->getChannel());
                    $this->strategyHelper
                        ->getEntityManager('OroCRMDotmailerBundle:AddressBookContact')
                        ->persist($addressBookContact);

                    $entity->addAddressBookContact($addressBookContact);
                }

                $addressBookContact->setStatus($entity->getStatus());
            } else {
                throw new RuntimeException(
                    sprintf('Address book for contact %s not found', $entity->getOriginId())
                );
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function findProcessedEntity($entity, array $searchContext = [])
    {
        if (!$entity instanceof Contact) {
            throw new RuntimeException('Entity of `\OroCRM\Bundle\DotmailerBundle\Entity\Contact` expected.');
        }

        if (!$entity->getEmail() || !$entity->getChannel()) {
            throw new RuntimeException("Channel and email required for contact {$entity->getOriginId()}");
        }

        /**
         * Fix case if this contact already imported on this batch  but for different address book
         */
        if (!$contact = $this->cacheProvider->getCachedItem(self::BATCH_ITEMS, $entity->getEmail())) {
            $contact = $this->findExistingContact($entity);

            $this->cacheProvider->setCachedItem(self::BATCH_ITEMS, $entity->getEmail(), $contact ?: $entity);
        }

        return $contact;
    }

    /**
     * @param Contact $entity
     *
     * @return mixed
     */
    protected function findExistingContact(Contact $entity)
    {
        /**
         * Two separated query used because of performance issue
         */
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

        if ($contact) {
            return $contact;
        }

        $contact = $this->getRepository('OroCRMDotmailerBundle:Contact')
            ->createQueryBuilder('contact')
            ->addSelect('addressBookContacts')
            ->addSelect('addressBook')
            ->where('contact.channel = :channel')
            ->andWhere('contact.originId = :originId')
            ->leftJoin('contact.addressBookContacts', 'addressBookContacts')
            ->leftJoin('addressBookContacts.addressBook', 'addressBook')
            ->setParameters(['channel' => $entity->getChannel(), 'originId' => $entity->getOriginId()])
            ->getQuery()
            ->useQueryCache(false)
            ->getOneOrNullResult();

        if ($contact) {
            $this->logger->info(
                "Email for Contact '{$contact->getOriginId()}' changed." .
                " From '{$contact->getEmail()}' to '{$entity->getEmail()}'"
            );
        }

        return $contact;
    }

    /**
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        $originalValue = $this->context->getValue('itemData');
        if (empty($originalValue[ContactIterator::ADDRESS_BOOK_KEY])) {
            throw new RuntimeException('Address book id required');
        }

        $addressBookOriginId = $originalValue[ContactIterator::ADDRESS_BOOK_KEY];

        return $this->getAddressBookByOriginId($addressBookOriginId);
    }
}
