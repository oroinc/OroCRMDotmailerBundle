<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0\AddEnumFields;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0\OroCRMDotmailerBundle;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_1\AddSyncDateColumns;
use OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_1\RemoveLastSyncedColumn;

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
        return 'v1_1';
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new OroCRMDotmailerBundle();
        $migration->up($schema, $queries);

        $addEnumFieldsMigration = new AddEnumFields();
        $addEnumFieldsMigration->setExtendExtension($this->extendExtension);
        $addEnumFieldsMigration->up($schema, $queries);

        $addSyncDateColumns = new AddSyncDateColumns();
        $addSyncDateColumns->addSyncDateColumns($schema);

        $removeLastSyncDate = new RemoveLastSyncedColumn();
        $removeLastSyncDate->up($schema, $queries);
    }


    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
