<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

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

    /**
     * @param ManagerRegistry $managerRegistry
     */
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
        $repository = $this->managerRegistry->getRepository('OroIntegrationBundle:Status');
        /**
         * @var Status $status
         */
        $status = $repository->findOneBy(
            [
                'code' => Status::STATUS_COMPLETED,
                'channel' => $this->getChannel(),
                'connector' => $this->getType()
            ],
            [
                'date' => 'DESC'
            ]
        );
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $context = $this->getStepExecution()->getExecutionContext();
        $data = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY) ?: [];
        $data[self::LAST_SYNC_DATE_KEY] = $date->format(\DateTime::ISO8601);
        $context->put(
            ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY,
            $data
        );
        if (!$status) {
            return null;
        }
        $data = $status->getData();
        if (empty($data) || empty($data[self::LAST_SYNC_DATE_KEY])) {
            return null;
        }

        return new \DateTime($data[self::LAST_SYNC_DATE_KEY], new \DateTimeZone('UTC'));
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->getContext());
    }
}
