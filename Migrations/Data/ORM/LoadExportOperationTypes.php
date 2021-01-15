<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;

class LoadExportOperationTypes extends AbstractEnumFixture
{
    /** @var array */
    protected $enumData = [
        'dm_ab_cnt_exp_type' => [
            AddressBookContact::EXPORT_NEW_CONTACT         => 'Export New Contact',
            AddressBookContact::EXPORT_UPDATE_CONTACT      => 'Update Contact',
            AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK => 'Add Contact to Address Book',
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
