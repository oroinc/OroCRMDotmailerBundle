<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class UnsubscribedFromAccountStrategy extends AbstractImportStrategy
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
        $contact->getAddressBooks()
            ->clear();
        
        $contact->setStatus(
            $this->getEnumValue('dm_cnt_status', Contact::STATUS_UNSUBSCRIBED)
        );

        return $contact;
    }
}
