<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class UnsubscribedFromAccountContactReader extends AbstractReader
{
    protected function initializeReader()
    {
        $this->logger->info('Importing Unsubscribed from Account Contacts');

        if (!$channel = $this->getChannel()) {
            $channelId = $this->context->getOption('channel');
            throw new RuntimeException("Channel $channelId not exist");
        }

        /** @var DotmailerTransport $transport */
        $transport = $this->contextMediator->getInitializedTransport($channel);
        $lastSyncDate = $this->getLastSyncDate();
        $iterator = $transport->getUnsubscribedFromAccountsContacts($lastSyncDate);
        $this->setSourceIterator($iterator);
    }

    /**
     * @return \DateTime|null
     */
    protected function getLastSyncDate()
    {
        /** @var EntityRepository $repository */
        $repository = $this->managerRegistry->getRepository('OroIntegrationBundle:Status');

        if ($lastSyncDate = $this->getConnectorLastSyncDate($repository, UnsubscribedContactConnector::TYPE)) {
            return $lastSyncDate;
        }

        if ($lastSyncDate = $this->getConnectorLastSyncDate($repository, ContactConnector::TYPE)) {
            return $lastSyncDate;
        }

        return null;
    }

    /**
     * @param EntityRepository $repository
     * @param string           $connectorType
     *
     * @return \DateTime|null
     */
    protected function getConnectorLastSyncDate(EntityRepository $repository, $connectorType)
    {
        $status = $repository->findOneBy(
            [
                'code'      => Status::STATUS_COMPLETED,
                'channel'   => $this->getChannel(),
                'connector' => $connectorType
            ],
            [
                'date' => 'DESC'
            ]
        );

        if (!$status) {
            return null;
        }

        $data = $status->getData();
        if (empty($data) || empty($data[AbstractDotmailerConnector::LAST_SYNC_DATE_KEY])) {
            return null;
        }

        return new \DateTime($data[AbstractDotmailerConnector::LAST_SYNC_DATE_KEY], new \DateTimeZone('UTC'));
    }
}
