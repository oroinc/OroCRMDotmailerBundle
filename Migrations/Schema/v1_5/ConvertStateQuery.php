<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\IntegrationBundle\Entity\State;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class ConvertStateQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Move old schedule state to new format'
        );
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $updateSql
            = 'INSERT INTO oro_integration_entity_state (entity_class, entity_id, state) '
            . 'SELECT :class, id, :state '
            . 'FROM orocrm_dm_ab_contact WHERE scheduled_for_export = :schedule';

        $this->logQuery($logger, $updateSql);

        $class = 'Oro\\Bundle\\DotmailerBundle\\Entity\\AddressBookContact';
        if ($this->connection->getDatabasePlatform()->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            $class = str_replace('\\', '\\\\', $class);
        }

        if (!$dryRun) {
            $this->connection->executeUpdate(
                $updateSql,
                ['schedule' => true, 'state' => State::STATE_SCHEDULED_FOR_EXPORT, 'class' => $class],
                ['schedule' => Type::BOOLEAN, 'state' => Type::INTEGER, 'class' => Type::STRING]
            );
        }
    }
}
