<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDotmailerDataFieldMapping implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroDotmailerDataFieldMappingTable($schema);
        self::createOroDotmailerDataFieldMappingConfigTable($schema);
    }

    /**
     * Create orocrm_dm_address_book table
     */
    public static function createOroDotmailerDataFieldMappingTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_df_mapping');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('sync_priority', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'orocrm_dm_data_field_mapping_owner', []);
        $table->addIndex(['channel_id'], 'orocrm_dm_data_field_mapping_channel', []);
        $table->addUniqueIndex(['entity', 'channel_id'], 'orocrm_dm_data_field_mapping_unq');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create orocrm_dm_address_book table
     */
    public static function createOroDotmailerDataFieldMappingConfigTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_df_mapping_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('mapping_id', 'integer', ['notnull' => false]);
        $table->addColumn('datafield_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_field', 'text');
        $table->addColumn('is_two_way_sync', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['mapping_id'], 'orocrm_dm_data_field_mapping_config_mapping', []);
        $table->addIndex(['datafield_id'], 'orocrm_dm_data_field_mapping_config_datafield', []);
        $table->addUniqueIndex(['datafield_id', 'mapping_id'], 'orocrm_dm_df_mapping_config_unq');

        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_df_mapping'),
            ['mapping_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_data_field'),
            ['datafield_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
