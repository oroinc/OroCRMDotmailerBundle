<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDotmailerDataField implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroDotmailerDataFieldTable($schema);
    }

    /**
     * Create orocrm_dm_address_book table
     */
    public static function createOroDotmailerDataFieldTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_data_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('default_value', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'orocrm_dm_data_field_owner', []);
        $table->addIndex(['channel_id'], 'orocrm_dm_data_field_channel', []);
        $table->addUniqueIndex(['name', 'channel_id'], 'orocrm_dm_data_field_unq');

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
}
