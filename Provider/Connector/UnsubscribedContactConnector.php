<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

/**
 * Contacts unsubscribed from Address book Connector
 */
class UnsubscribedContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'unsubscribed_contact';
    const IMPORT_JOB = 'dotmailer_unsubscribed_contact_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Unsubscribed from Address Book Contacts.');
        $addressBooks = $this->managerRegistry->getRepository('OroDotmailerBundle:AddressBook')
            ->getAddressBooksToSync($this->getChannel());

        return $this->transport->getUnsubscribedContacts($addressBooks);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.unsubscribed_contact.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
