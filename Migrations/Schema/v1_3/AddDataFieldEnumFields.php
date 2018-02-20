<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDataFieldEnumFields implements Migration, ExtendExtensionAwareInterface, OrderedMigrationInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var array */
    protected $enumData = [
        'orocrm_dm_data_field' => [
            'dm_df_visibility'  => [
                'field'   => 'visibility',
            ],
            'dm_df_type'  => [
                'field'   => 'type',
            ],
        ],
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
                    false,
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
