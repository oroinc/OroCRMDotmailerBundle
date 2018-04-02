<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEnumFields implements Migration, ExtendExtensionAwareInterface, OrderedMigrationInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var array */
    protected $enumData = [
        // table_name => [enumCode => enumField],
        'orocrm_dm_campaign' => [
            'dm_cmp_reply_action'  => [
                'field'   => 'reply_action',
            ],
            'dm_cmp_status' => [
                'field'   => 'status',
            ],
        ],
        'orocrm_dm_address_book' => [
            'dm_ab_visibility'  => [
                'field'   => 'visibility',
            ],
            'dm_import_status'  => [
                'field'   => 'syncStatus',
            ],
        ],
        'orocrm_dm_contact' => [
            'dm_cnt_opt_in_type'  => [
                'field'   => 'opt_in_type',
            ],
            'dm_cnt_email_type'  => [
                'field'   => 'email_type',
            ],
            'dm_cnt_status'  => [
                'field'   => 'status',
            ],
        ],
        'orocrm_dm_ab_cnt_export' => [
            'dm_import_status'  => [
                'field'   => 'status',
            ],
        ],
        'orocrm_dm_ab_contact' => [
            'dm_cnt_status'  => [
                'field'   => 'status',
            ],
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // add private single-values enums
        foreach ($this->enumData as $tableName => $enumFields) {
            foreach ($enumFields as $enumCode => $enumField) {
                // detect options
                if (is_array($enumField)) {
                    $isMulti = empty($enumField['multi']) ? false : $enumField['multi'];
                    $enumField = $enumField['field'];
                    $options = empty($enumField['options']) ? [] : $enumField['options'];
                } else {
                    $isMulti = false;
                    $options = [];
                }

                $options = array_merge(
                    [
                        'extend' => ['owner' => ExtendScope::OWNER_CUSTOM]
                    ],
                    $options
                );

                $this->extendExtension->addEnumField(
                    $schema,
                    $schema->getTable($tableName),
                    $enumField,
                    $enumCode,
                    $isMulti, // only one option can be selected
                    true, // an administrator can add new options and remove existing ones
                    $options
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
