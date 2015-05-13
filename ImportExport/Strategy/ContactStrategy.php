<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
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
            $addressBook = $this->getAddressBook($entity->getChannel());
            if ($addressBook) {
                $addressBookContact = $this->getAddressBookContact($addressBook);

                if (is_null($addressBookContact)) {
                    $addressBookContact = new AddressBookContact();
                    $addressBookContact->setAddressBook($addressBook);
                    $entity->addAddressBookContact($addressBookContact);
                } else {
                    $addressBookContact->setStatus($entity->getStatus());
                }
            } else {
                throw new RuntimeException(
                    sprintf('Address book for campaign %s not found', $entity->getOriginId())
                );
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Integration $channel
     *
     * @return AddressBook
     */
    protected function getAddressBook(Integration $channel)
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

    /**
     * @param AddressBook $addressBook
     *
     * @return AddressBookContact|null
     */
    protected function getAddressBookContact(AddressBook $addressBook)
    {
        foreach ($addressBook->getAddressBookContacts() as $addressBookContact) {
            if ($addressBookContact->getAddressBook() == $addressBook) {
                return $addressBookContact;
            }
        }

        return null;
    }
}
