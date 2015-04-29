<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class UnsubscribedFromAccountContactsReader extends IteratorBasedReader
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ConnectorContextMediator
     */
    protected $contextMediator;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param ConnectorContextMediator $contextMediator
     * @param ManagerRegistry          $managerRegistry
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $contextMediator,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($contextRegistry);
        $this->contextMediator = $contextMediator;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->context = $context;
        $channel = $this->getChannel();
        /** @var DotmailerTransport $transport */
        $transport = $this->contextMediator->getInitializedTransport($channel);

        $lastSyncDate = $this->getLastSyncDate();
        $iterator = $transport->getUnsubscribedFromAccountsContacts($lastSyncDate);


        $this->setSourceIterator($iterator);
    }


    protected function getLastSyncDate()
    {
        $repository = $this->managerRegistry->getRepository('OroIntegrationBundle:Status');

        /** @var Status $status */
        $status = $repository->findOneBy(
            [
                'code' => Status::STATUS_COMPLETED,
                'channel' => $this->getChannel(),
                'connector' => UnsubscribedContactsConnector::TYPE
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

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->context);
    }
}
