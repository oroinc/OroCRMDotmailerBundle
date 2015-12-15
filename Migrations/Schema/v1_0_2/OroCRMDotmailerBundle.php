<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMDotmailerBundle implements Migration
{
    /** @var ExtendExtension */
    protected $extendExtension;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_ab_cnt_export');
        $table->addColumn('faults_processed', 'boolean', []);
        $table->addIndex(['faults_processed'], 'orocrm_dm_ab_cnt_exp_fault_idx', []);

        $table = $schema->getTable('orocrm_dm_ab_contact');
        $table->addColumn('export_id', 'string', ['notnull' => false, 'length' => 36]);
        $table->addIndex(['export_id'], 'orocrm_dm_ab_cnt_export_id_idx', []);

        $this->extendExtension->addEnumField(
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
    }
}
