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
    public function afterProcessEntity($entity)
    {
        /** @var Contact $entity */
        if ($entity) {
            $batchItems = $this->context->getValue(self::BATCH_ITEMS);

            /**
             * Fix case if this contact already imported on this batch
             */
            $isEntityExists = false;
            if ($batchItems && !$entity->getId() && isset($batchItems[$entity->getOriginId()])) {
                $entity = $batchItems[$entity->getOriginId()];
                $isEntityExists = true;
            }
            $addressBook = $this->getAddressBook($entity->getChannel());
            if ($addressBook) {
                if ($entity->getId() === 0) {
                    $errorMessage = implode(
                        PHP_EOL,
                        [
                            'Dotmailer Contact Strategy Error: Contact Id is 0',
                            'Address Book Id ' . $addressBook->getId(),
                            'Contact OriginId ' . $entity->getOriginId(),
                            'Contact Email ' . $entity->getEmail(),
                            'Contact Status ' . $entity->getStatus()->getName(),
                            'Contact First Name ' . $entity->getFirstName(),
                            'Contact Last Name ' . $entity->getLastName(),
                            'Contact Created At ' . $entity->getCreatedAt()->format(\DateTime::ISO8601),
                            'Contact Updated At ' . $entity->getUpdatedAt()->format(\DateTime::ISO8601),
                            'Original Value: ' . print_r($this->context->getValue('itemData'), true),
                        ]
                    );
                    $this->context->addError($errorMessage);

                    return null;
                }

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
                    $entity->addAddressBookContact($addressBookContact);
                }

                $addressBookContact->setStatus($entity->getStatus());

                if ($isEntityExists) {
                    return null;
                }
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
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        /**
         * Required for match contact after export new one to dotmailer
         */
        if ($entity instanceof Contact) {
            if (!$entity->getEmail() || !$entity->getChannel()) {
                throw new RuntimeException("Channel and email required for contact {$entity->getOriginId()}");
            }

            return $this->getRepository('OroCRMDotmailerBundle:Contact')
                ->createQueryBuilder('contact')
                ->addSelect('addressBookContacts')
                ->addSelect('addressBook')
                ->where('contact.channel = :channel')
                ->andWhere('contact.email = :email')
                ->leftJoin('contact.addressBookContacts', 'addressBookContacts')
                ->innerJoin('addressBookContacts.addressBook', 'addressBook')
                ->setMaxResults(1)
                ->setParameters(['channel' => $entity->getChannel(), 'email' => $entity->getEmail()])
                ->getQuery()
                ->useQueryCache(false)
                ->getOneOrNullResult();
        } else {
            return parent::findExistingEntity($entity, $searchContext);
        }
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

        $addressBookOriginId = $originalValue[ContactIterator::ADDRESS_BOOK_KEY];

        $cachedAddressBooks = $this->context->getValue('cachedAddressBookEntities');
        if (!$cachedAddressBooks || !isset($cachedAddressBooks[$addressBookOriginId])) {
            $addressBook = $this->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->findOneBy(
                    [
                        'channel'  => $channel,
                        'originId' => $addressBookOriginId
                    ]
                );

            $this->context->setValue('cachedAddressBookEntities', [$addressBookOriginId => $addressBook]);
        } else {
            $addressBook = $this->reattachDetachedEntity($cachedAddressBooks[$addressBookOriginId]);

            $cachedAddressBooks[$addressBookOriginId] = $addressBook;
            $this->context->setValue('cachedAddressBookEntities', $cachedAddressBooks);
        }

        return $addressBook;
    }
}
