<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Installer executes all needed table changes during install
 */
class OroDotmailerBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new v1_0\OroDotmailerBundle();
        $migration->up($schema, $queries);

        $addEnumFieldsMigration = new v1_0\AddEnumFields();
        $addEnumFieldsMigration->setExtendExtension($this->extendExtension);
        $addEnumFieldsMigration->up($schema, $queries);

        $migration = new v1_2\AddActivityIndexes();
        $migration->up($schema, $queries);

        $migration = new v1_2\OroDotmailerBundle();
        $migration->setExtendExtension($this->extendExtension);
        $migration->up($schema, $queries);

        $addSyncDateColumns = new v1_2\AddSyncDateColumns();
        $addSyncDateColumns->addLastImportedAt($schema);

        $migration = new v1_3\AddDotmailerDataField();
        $migration->up($schema, $queries);

        $addEnumFieldsMigration = new v1_3\AddDataFieldEnumFields();
        $addEnumFieldsMigration->setExtendExtension($this->extendExtension);
        $addEnumFieldsMigration->up($schema, $queries);

        $migration = new v1_3\AddDotmailerDataFieldMapping();
        $migration->up($schema, $queries);

        $migration = new v1_3\AddDataFieldsToContact();
        $migration->up($schema, $queries);

        $migration = new v1_3\AddCreateEntitiesFlag();
        $migration->up($schema, $queries);

        $migration = new v1_3\AddUpdateFlagToAbContact();
        $migration->up($schema, $queries);

        $migration = new v1_3\AddChangeFieldLogTable();
        $migration->up($schema, $queries);

        $migration = new v1_3\AddIndexToAbContact();
        $migration->up($schema, $queries);

        $migration = new v1_3\OroDotmailerBundle();
        $migration->up($schema, $queries);

        $migration = new v1_8\AddSyncAttemptsToAbContactsExport();
        $migration->up($schema, $queries);

        $this->renameLastSyncedColumn($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    protected function renameLastSyncedColumn(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->dropColumn('last_synced');
        $table->addColumn('last_exported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
    }
}
