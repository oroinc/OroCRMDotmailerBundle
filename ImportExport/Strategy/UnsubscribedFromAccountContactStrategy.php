<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

/**
 * Strategy for import Contact entities
 */
class UnsubscribedFromAccountContactStrategy extends AbstractImportStrategy
{
    #[\Override]
    public function process($entity)
    {
        if (!$entity instanceof Contact) {
            throw new RuntimeException(
                sprintf(
                    'Argument must be an instance of "%s", but "%s" is given',
                    'Oro\Bundle\DotmailerBundle\Entity\Contact',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->getChannel()) {
            throw new RuntimeException('Channel not found');
        }

        $contact = $this->registry->getRepository(Contact::class)
            ->findOneBy(['email' => $entity->getEmail(), 'channel' => $this->getChannel()]);
        if (!$contact) {
            $this->context->addError("Contact {$entity->getOriginId()} not found.");
            $this->context->incrementErrorEntriesCount();

            return null;
        }

        $reason = $this->getEnumValue($entity->getStatus()->getId());
        foreach ($contact->getAddressBookContacts() as $addressBookContact) {
            $addressBookContact->setStatus($reason);
            $addressBookContact->setUnsubscribedDate($entity->getUnsubscribedDate());
        }
        $contact->setStatus($reason);
        $contact->setUnsubscribedDate($entity->getUnsubscribedDate());

        $this->context->incrementUpdateCount();

        return $contact;
    }
}
