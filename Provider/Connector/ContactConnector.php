<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class ContactConnector extends AbstractDotmailerConnector implements TwoWaySyncConnectorInterface
{
    const TYPE = 'contact';
    const IMPORT_JOB = 'dotmailer_new_contacts';
    const EXPORT_JOB = 'dotmailer_contact_export';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSyncOriginIds($this->getChannel());

        return $this->transport->getAddressBookContacts($aBooksToSynchronize, $this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.contact.label';
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

    /**
     * @return string
     */
    public function getExportJobName()
    {
        return self::EXPORT_JOB;
    }
}
