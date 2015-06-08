<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

class NotExportedContactStrategy extends AbstractImportStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof AddressBookContact) {
            $type = is_object($entity) ? get_class($entity) : gettype($entity);
            $this->context->addError(
                sprintf(
                    'Instance of %s expected. Instance of %s given.',
                    'OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact',
                    $type
                )
            );

            return null;
        }

        if (!$email = $entity->getContact()->getEmail()) {
            $this->context->addError('Email field required for import '. $this->getImport());

            return null;
        }

        $contact = $this->registry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['email' => $email, 'channel' => $this->getChannel()]);

        if (!$contact) {
            $this->context->addError("Contact is not exist for email $email");

            return null;
        }

        $addressBook = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->find($entity->getAddressBook()->getId());

        if (!$addressBook) {
            $this->context->addError("Address Book with id {$addressBook->getId()} is not exist");

            return null;
        }

        $addressBookContact = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(['contact' => $contact, 'addressBook' => $addressBook]);

        if (!$addressBookContact) {
            $addressBookContact = new AddressBookContact();
            $addressBookContact->setAddressBook($addressBook);
            $addressBookContact->setContact($contact);
            $addressBookContact->setChannel($this->getChannel());
        }

        $reason = $this->getEnumValue('dm_cnt_status', Contact::STATUS_SUPPRESSED);
        $addressBookContact->setStatus($reason);
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $addressBookContact->setUnsubscribedDate($currentDate);
        $contact->setStatus($reason);
        $contact->setUnsubscribedDate($currentDate);

        return $addressBookContact;
    }

    protected function getImport()
    {
        $originalValue = $this->context->getValue('itemData');

        if (empty($originalValue['import'])) {
            throw new RuntimeException('Import Id is required');
        }

        return $originalValue['import'];
    }
}
