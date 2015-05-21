<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;

class LoadAddressBookContactsExportData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'status'      => AddressBookContactsExport::STATUS_NOT_FINISHED,
            'addressBook' => 'orocrm_dotmailer.address_book.fifth',
            'channel'     => 'orocrm_dotmailer.channel.fourth',
            'importId'    => '1fb9cba7-e588-445a-8731-4796c86b1097',
            'reference'   => 'orocrm_dotmailer.address_book_contacts_export'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $addressBookContactExport = new AddressBookContactsExport();
            $item['status'] = $this->findEnum('dm_import_status', $item['status']);

            $this->resolveReferenceIfExist($item, 'addressBook');
            $this->resolveReferenceIfExist($item, 'channel');
            $this->setEntityPropertyValues($addressBookContactExport, $item, ['reference']);

            $manager->persist($addressBookContactExport);
            $this->addReference($item['reference'], $addressBookContactExport);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData'
        ];
    }
}
