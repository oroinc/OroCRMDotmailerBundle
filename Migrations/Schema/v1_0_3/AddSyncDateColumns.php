<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSyncDateColumns implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameLastSyncDateColumn($schema, $queries);
        $this->addLastImportedAt($schema);
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public function renameLastSyncDateColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'last_synced', 'last_exported_at');
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

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @param Schema $schema
     */
    public function addLastImportedAt(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addColumn('last_imported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addIndex(['last_imported_at'], 'orocrm_dm_ab_imported_at_idx', []);
    }
}
