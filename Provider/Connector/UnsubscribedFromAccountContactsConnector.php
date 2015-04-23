<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class UnsubscribedFromAccountContactsConnector extends AbstractDotmailerConnector
{
    const TYPE = 'unsubscribed_from_account_contacts';
    const IMPORT_JOB = 'dotmailer_unsubscribed_from_account_contacts_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $lastSyncDate = $this->getLastSyncDate();
        if (!$lastSyncDate) {
            return new \EmptyIterator();
        }

        return $this->transport->getUnsubscribedFromAccountsContacts($lastSyncDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.unsubscribed_from_account_contacts.label';
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
