<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DotmailerBundle\Migration\AddContactExportConnectorToExistedIntegrationsQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDotmailerBundle implements Migration, OutdatedExtendExtensionAwareInterface
{
    use OutdatedExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_ab_cnt_export');
        if (!$table->hasColumn('faults_processed')) {
            $table->addColumn('faults_processed', 'boolean', []);
            $table->addIndex(['faults_processed'], 'orocrm_dm_ab_cnt_exp_fault_idx', []);
        }

        $table = $schema->getTable('orocrm_dm_ab_contact');
        if (!$table->hasColumn('export_id')) {
            $table->addColumn('export_id', 'string', ['notnull' => false, 'length' => 36]);
            $table->addIndex(['export_id'], 'orocrm_dm_ab_cnt_export_id_idx', []);
        }

        $tableName = $this->outdatedExtendExtension->getTableNameByEntityClass(
            OutdatedExtendExtension::buildEnumValueClassName('dm_ab_cnt_exp_type')
        );
        if (!$tableName || !$schema->hasTable($tableName)) {
            $this->outdatedExtendExtension->addOutdatedEnumField(
                $schema,
                $schema->getTable('orocrm_dm_ab_contact'),
                'exportOperationType',
                'dm_ab_cnt_exp_type',
                false,
                true,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
                ]
            );

            $queries->addPostQuery(new AddContactExportConnectorToExistedIntegrationsQuery());
        }
    }
}
