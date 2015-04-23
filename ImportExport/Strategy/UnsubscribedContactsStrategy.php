<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactsIterator;

class UnsubscribedContactsStrategy extends AbstractImportStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$entity instanceof Contact) {
            throw new \RuntimeException(
                sprintf(
                    'Argument must be an instance of "%s", but "%s" is given',
                    'OroCRM\Bundle\DotmailerBundle\Entity\Contact',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if (!$this->getChannel()) {
            throw new \RuntimeException('Channel not found');
        }

        $contact = $this->registry->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['originId' => $entity->getOriginId(), 'channel' => $this->getChannel()]);

        $addressBook = $this->getAddressBook();
        $contact->removeAddressBook($addressBook);

        return $contact;
    }

    /**
     * @return AddressBook
     */
    protected function getAddressBook()
    {
        $originalValue = $this->context->getValue('itemData');

        if (empty($originalValue[UnsubscribedContactsIterator::ADDRESS_BOOK_KEY])) {
            throw new \RuntimeException('Address book id required');
        }
        $addressBook = $this->registry->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(
                [
                    'channel' => $this->getChannel(),
                    'originId' => $originalValue[UnsubscribedContactsIterator::ADDRESS_BOOK_KEY]
                ]
            );

        return $addressBook;
    }
}
