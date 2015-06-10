<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Entity\Status;

class UnsubscribedContactsConnector extends AbstractDotmailerConnector
{
    const TYPE = 'unsubscribed_contacts';
    const IMPORT_JOB = 'dotmailer_unsubscribed_contacts_import';

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
    public function getLastSyncDate()
    {
        $status = $this->getLastStatus();
        if (!$status) {
            $repository = $this->managerRegistry->getRepository('OroIntegrationBundle:Status');
            $status = $repository->findOneBy(
                [
                    'code'      => Status::STATUS_COMPLETED,
                    'channel'   => $this->getChannel(),
                    'connector' => ContactConnector::TYPE
                ],
                [
                    'date' => 'ASC'
                ]
            );

            if (!$status) {
                return null;
            }
        }

        $date = null;
        $statusData = $status->getData();
        if (false == empty($statusData[self::LAST_SYNC_DATE_KEY])) {
            $date = new \DateTime($statusData[self::LAST_SYNC_DATE_KEY], new \DateTimeZone('UTC'));
        }

        return $date;
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
