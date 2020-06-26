<?php

namespace Oro\Bundle\DotmailerBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * A custom query which is responsible to add new connector "ExportContactConnector::TYPE"
 * to the existing integrations with the Dotmailer
 */
class AddContactExportConnectorToExistedIntegrationsQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $integrations = $this->getDotmailerIntegrations();

        $integrationIds = array_map(function (array $integration) {
            return $integration['id'];
        }, $integrations);

        return sprintf(
            'Contact export connector added to integrations %s',
            implode(',', $integrationIds)
        );
    }

    /**
     * Executes a query
     *
     * @param LoggerInterface $logger A logger which can be used to log details of an execution process
     */
    public function execute(LoggerInterface $logger)
    {
        $integrations = $this->getDotmailerIntegrations();

        $updateIntegrationQuery = <<<SQL
        UPDATE oro_integration_channel as integration_channel
        SET integration_channel.connectors = :connectors
        WHERE integration_channel.id = :id
SQL;

        foreach ($integrations as $integration) {
            $params = [
                'id' => $integration['id'],
                'connectors' => $this->prepareConnectors($integration['connectors'])
            ];

            $this->connection->executeQuery($updateIntegrationQuery, $params);
            $this->logQuery($logger, $updateIntegrationQuery, $params);
        }
    }

    protected function getDotmailerIntegrations()
    {
        $parameters = ['type' => ChannelType::TYPE];

        $integrations = $this->connection
            ->fetchAll(
                'SELECT * FROM oro_integration_channel as integration_channel WHERE integration_channel.type = :type',
                $parameters
            );

        return $integrations;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $query
     * @param array           $params
     */
    protected function logQuery(LoggerInterface $logger, $query, array $params)
    {
        $logger->warning(
            sprintf(
                'Query % was executed with params %s',
                $query,
                json_encode($params)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $connectors
     *
     * @return string
     */
    private function prepareConnectors($connectors)
    {
        $type = Type::getType(Types::ARRAY);
        $platform = $this->connection->getDatabasePlatform();
        $deserializedConnectors = $type->convertToPHPValue($connectors, $platform);
        $deserializedConnectors[] = ExportContactConnector::TYPE;

        return $type->convertToDatabaseValue($deserializedConnectors, $platform);
    }
}
