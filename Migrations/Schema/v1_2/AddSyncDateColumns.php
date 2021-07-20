<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_2;

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

    public function renameLastSyncDateColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        if ($table->hasColumn('last_synced')) {
            $this->renameExtension->renameColumn($schema, $queries, $table, 'last_synced', 'last_exported_at');
        }
    }

    public function addLastImportedAt(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        if (!$table->hasColumn('last_imported_at')) {
            $table->addColumn('last_imported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
            $table->addIndex(['last_imported_at'], 'orocrm_dm_ab_imported_at_idx', []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
