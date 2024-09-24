<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Abstract base connector for Dotmailer integration.
 *
 * This class provides common functionalities for Dotmailer connectors,
 * handling synchronization processes including management of last synchronization date,
 * accessing channel information, and initializing context for data synchronization.
 * It extends from the AbstractConnector and implements necessary methods for Dotmailer integration.
 *
 * @property DotmailerTransport $transport
 */
abstract class AbstractDotmailerConnector extends AbstractConnector
{
    const LAST_SYNC_DATE_KEY = 'lastSyncDate';

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var string
     */
    protected $entityName;

    public function setManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastSyncDate()
    {
        $status = $this->getLastStatus();
        if (!$status) {
            return null;
        }

        $date = null;
        $statusData = $status->getData();
        if (false == empty($statusData[self::LAST_SYNC_DATE_KEY])) {
            $date = new \DateTime($statusData[self::LAST_SYNC_DATE_KEY], new \DateTimeZone('UTC'));
        }

        return $date;
    }

    /**
     * Get last status, by default - completed, otherwise - $code can be used
     * to filter by status code
     *
     * @param null|int $code
     *
     * @return Status
     */
    protected function getLastStatus($code = null)
    {
        $code = $code ?: Status::STATUS_COMPLETED;

        /** @var Status $status */
        $status = $this->managerRegistry->getRepository(Status::class)
            ->findOneBy(
                [
                    'code'      => $code,
                    'channel'   => $this->getChannel(),
                    'connector' => $this->getType()
                ],
                ['date' => 'DESC']
            );

        return $status;
    }

    #[\Override]
    protected function initializeFromContext(ContextInterface $context)
    {
        if (!$this->contextMediator->getChannel($context)) {
            throw new RuntimeException("Channel {$context->getOption('channel')} not exist");
        }

        parent::initializeFromContext($context);

        // updating context sync date with current date before actual sync
        $this->updateContextLastSyncDate();
    }

    /**
     * Updates last sync date in execution context with current date and time (now)
     */
    protected function updateContextLastSyncDate(\DateTime $date = null)
    {
        $context = $this->getStepExecution()->getExecutionContext();
        $data = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY) ?: [];

        $date = $date ?: new \DateTime('now', new \DateTimeZone('UTC'));
        $data[self::LAST_SYNC_DATE_KEY] = $date->format(\DateTime::ISO8601);

        $context->put(
            ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY,
            $data
        );
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->getContext());
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }
}
