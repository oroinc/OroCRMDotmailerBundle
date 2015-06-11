<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

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
        $addressBooks = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSyncOriginIds($this->getChannel());

        $lastSyncDate = $this->getLastSyncDate();
        return $this->transport->getUnsubscribedContacts($addressBooks, $lastSyncDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.unsubscribed_contacts.label';
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
