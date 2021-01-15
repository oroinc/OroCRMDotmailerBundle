<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\DataField;

class LoadDataFieldEnumValues extends AbstractEnumFixture
{
    /** @var array */
    protected $enumData = [
        'dm_df_visibility' => [
            DataField::VISIBILITY_PRIVATE                     => 'Private',
            DataField::VISIBILITY_PUBLIC                      => 'Public',
        ],
        'dm_df_type' => [
            DataField::FIELD_TYPE_STRING                      => 'String',
            DataField::FIELD_TYPE_NUMERIC                     => 'Numeric',
            DataField::FIELD_TYPE_DATE                        => 'Date',
            DataField::FIELD_TYPE_BOOLEAN                     => 'Boolean',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManagerInterface $manager */
        $this->loadEnumValues($this->enumData, $manager);
    }
}
