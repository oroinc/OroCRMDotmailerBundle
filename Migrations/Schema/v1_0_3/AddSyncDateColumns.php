<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSyncDateColumns implements Migration, OrderedMigrationInterface
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addSyncDateColumns($schema);

        $dql = <<<DQL
            UPDATE orocrm_dm_address_book as ab
            SET ab.last_exported_at = ab.last_synced
            WHERE ab.last_synced IS NOT NULL
DQL;

        $queries->addPostQuery($dql);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addSyncDateColumns(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addColumn('last_exported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addColumn('last_imported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addIndex(['last_imported_at'], 'orocrm_dm_ab_imported_at_idx', []);
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 0;
    }
}
