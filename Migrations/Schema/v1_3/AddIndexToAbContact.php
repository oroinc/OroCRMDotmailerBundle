<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIndexToAbContact implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndexToAbContact($schema);
    }

    protected function addIndexToAbContact(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_ab_contact');
        $table->addIndex(['marketing_list_item_class', 'marketing_list_item_id'], 'IDX_MARKETING_LIST_ITEM_CLASS_ID');
    }
}
