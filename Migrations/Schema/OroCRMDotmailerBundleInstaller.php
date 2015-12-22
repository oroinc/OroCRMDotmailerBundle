<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_2;

class OroCRMDotmailerBundleInstaller implements Installation, ExtendExtensionAwareInterface, NameGeneratorAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new v1_0\OroCRMDotmailerBundle();
        $migration->up($schema, $queries);

        $addEnumFieldsMigration = new v1_0\AddEnumFields();
        $addEnumFieldsMigration->setExtendExtension($this->extendExtension);
        $addEnumFieldsMigration->up($schema, $queries);

        $migration = new v1_2\AddActivityIndexes();
        $migration->up($schema, $queries);

        $migration = new v1_2\OroCRMDotmailerBundle();
        $migration->setExtendExtension($this->extendExtension);
        $migration->setNameGenerator($this->nameGenerator);
        $migration->up($schema, $queries);

        $addSyncDateColumns = new v1_2\AddSyncDateColumns();
        $addSyncDateColumns->addLastImportedAt($schema);

        $this->renameLastSyncedColumn($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @param Schema $schema
     */
    protected function renameLastSyncedColumn(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->dropColumn('last_synced');
        $table->addColumn('last_exported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
    }
}
