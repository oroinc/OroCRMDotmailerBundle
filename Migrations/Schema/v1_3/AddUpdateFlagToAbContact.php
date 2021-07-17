<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUpdateFlagToAbContact implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addUpdateFlagToAbContact($schema);
    }

    protected function addUpdateFlagToAbContact(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_ab_contact');
        $table->addColumn('entity_updated', 'boolean', ['notnull' => false]);
        $table->addColumn('scheduled_for_fields_update', 'boolean', ['notnull' => false]);
    }
}
