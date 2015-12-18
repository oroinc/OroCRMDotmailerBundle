<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_1\AddActivityIndexes;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_3\AddSyncDateColumns;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_3\RemoveLastSyncedColumn;

class OroCRMDotmailerBundleInstaller implements Installation, ExtendExtensionAwareInterface
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
        return 'v1_0_3';
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

        $addSyncDateColumns = new AddSyncDateColumns();
        $addSyncDateColumns->addSyncDateColumns($schema);

        $removeLastSyncDate = new RemoveLastSyncedColumn();
        $removeLastSyncDate->up($schema, $queries);

        $activityIndexes = new AddActivityIndexes();
        $activityIndexes->up($schema, $queries);
    }


    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
