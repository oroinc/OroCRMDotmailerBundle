<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;

class LoadExportOperationTypes extends AbstractEnumFixture
{
    /** @var array */
    protected $enumData = [
        'dm_ab_cnt_exp_type' => [
            AddressBookContact::EXPORT_NEW_CONTACT         => AddressBookContact::EXPORT_NEW_CONTACT,
            AddressBookContact::EXPORT_UPDATE_CONTACT      => AddressBookContact::EXPORT_UPDATE_CONTACT,
            AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK => AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK,
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
