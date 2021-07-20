<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddChangeFieldLogTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addChangeFieldLogTable($schema);
    }

    protected function addChangeFieldLogTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_change_field_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer');
        $table->addColumn('parent_entity', 'string', ['length' => 255]);
        $table->addColumn('related_field_path', 'text');
        $table->addColumn('related_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }
}
