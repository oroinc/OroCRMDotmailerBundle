<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCreateEntitiesFlag implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroDotmailerAddressBookTable($schema);
        $this->updateOroDotmailerAddressBookContactTable($schema);
    }

    /**
     * Update orocrm_dm_ab_contact table
     */
    protected function updateOroDotmailerAddressBookTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addColumn('create_entities', 'boolean', ['notnull' => false]);
    }

    protected function updateOroDotmailerAddressBookContactTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_ab_contact');
        $table->addColumn('new_entity', 'boolean', ['notnull' => false]);
    }
}
