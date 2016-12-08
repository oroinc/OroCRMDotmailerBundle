<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDotmailerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroDotmailerAddressBookTable($schema);
    }

    /**
     * Update orocrm_dm_ab_contact table
     *
     * @param Schema $schema
     */
    protected function updateOroDotmailerAddressBookTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addColumn('is_create_entities', 'boolean', ['notnull' => false]);
    }
}
