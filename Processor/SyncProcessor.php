<?php

namespace Oro\Bundle\DotmailerBundle\Processor;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor as BaseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Provider\RedeliverableInterface;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ParallelizableInterface;

class SyncProcessor extends BaseSyncProcessor implements RedeliverableInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**@var MessageProducerInterface */
    private $messageProducer;

    /** @var bool */
    private $needRedelivery = false;

    /**
     * @param DoctrineHelper $doctrineHelper
     *
     * @return SyncProcessor
     */
    public function setDoctrineHelper($doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;

        return $this;
    }

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function setMessageProducer(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Integration $integration, $connector = null, array $parameters = [])
    {
        $this->needRedelivery = false;

        $integration = $this->reloadEntity($integration);
        if (!$this->isParallelProcess($parameters)) {
            $this->scheduleInitialSyncIfRequired($integration, $connector, $parameters);
        }

        return parent::process($integration, $connector, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function needRedelivery()
    {
        return $this->needRedelivery;
    }

    /**
     * {@inheritdoc}
     */
    protected function onProcessIntegrationConnector(Status $status)
    {
        if ($status->getCode() === Status::STATUS_REQUEUE) {
            $this->needRedelivery = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function scheduleInitialSyncIfRequired(Integration $integration, $connector, $parameters)
    {
        $this->logger->info('Scheduling address book synchronization');

        if (array_key_exists(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION, $parameters)) {
            $this->produceMessage($integration, $connector, $parameters);

            return true;
        }

        $hasProduces = false;
        $addressBooks = $this->doctrineRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->getConnectedAddressBooks($integration);
        foreach ($addressBooks as $addressBook) {
            $newParams = $parameters;
            $newParams[AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION] = $addressBook->getId();
            $this->produceMessage($integration, $connector, $newParams);
            $hasProduces = true;
        }

        return $hasProduces;
    }

    /**
     * @param object $entity
     *
     * @return Integration
     */
    protected function reloadEntity($entity)
    {
        return $this->doctrineHelper->getEntity(
            $this->doctrineHelper->getEntityClass($entity),
            $this->doctrineHelper->getEntityIdentifier($entity)
        );
    }

    /**
     * @param array $parameters
     *
     * @return bool
     */
    protected function isParallelProcess($parameters)
    {
        return isset($parameters['parallel-process']);
    }

    /**
     * @param Integration $integration
     * @param $connector
     * @param array $parameters
     */
    protected function produceMessage(Integration $integration, $connector, $parameters)
    {
        $parameters['parallel-process'] = true;
        $this->messageProducer->send(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id'       => $integration->getId(),
                    'connector_parameters' => $parameters,
                    'connector'            => $connector,
                    'transport_batch_size' => 100
                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function formatResultMessage(ContextInterface $context = null)
    {
        if ($context && $context->hasOption(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION)) {
            $addressBook = $this->doctrineRegistry
                ->getRepository('OroDotmailerBundle:AddressBook')
                ->find($context->getOption(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION));
            return sprintf(
                '%s, address book: [id - %s; name - %s]',
                parent::formatResultMessage($context),
                $addressBook->getId(),
                $addressBook->getName()
            );
        }
        return parent::formatResultMessage($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function isConnectorAllowedParametrized(
        ConnectorInterface $connector,
        Integration $integration,
        array $processedConnectorStatuses,
        array $parameters = []
    ) {
        if (!$this->isParallelProcess($parameters) && $connector instanceof ParallelizableInterface) {
            return false;
        }

        if ($this->isParallelProcess($parameters) && !$connector instanceof ParallelizableInterface) {
            return false;
        }

        return parent::isConnectorAllowedParametrized(
            $connector,
            $integration,
            $processedConnectorStatuses,
            $parameters
        );
    }
}
