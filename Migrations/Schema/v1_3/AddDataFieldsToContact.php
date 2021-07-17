<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDataFieldsToContact implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addDataFieldsToContact($schema);
    }

    protected function addDataFieldsToContact(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_contact');
        $table->addColumn('data_fields', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
    }
}
