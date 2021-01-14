<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;

class LoadAddressBookContactsExportData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'status'      => AddressBookContactsExport::STATUS_NOT_FINISHED,
            'addressBook' => 'oro_dotmailer.address_book.fifth',
            'channel'     => 'oro_dotmailer.channel.fourth',
            'importId'    => '1fb9cba7-e588-445a-8731-4796c86b1097',
            'contacts'    => ['oro_dotmailer.contact.update_1'],
            'reference'   => 'oro_dotmailer.address_book_contacts_export.first'
        ],
        [
            'status'      => AddressBookContactsExport::STATUS_FINISH,
            'addressBook' => 'oro_dotmailer.address_book.six',
            'channel'     => 'oro_dotmailer.channel.fourth',
            'importId'    => '6fb9cba7-e588-445a-8731-4796c86b1097',
            'contacts'    => ['oro_dotmailer.contact.allen_case'],
            'reference'   => 'oro_dotmailer.address_book_contacts_export.add_to_address_book'
        ],
        [
            'status'      => AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG,
            'addressBook' => 'oro_dotmailer.address_book.six',
            'channel'     => 'oro_dotmailer.channel.fourth',
            'importId'    => '5fb9cba7-e588-445a-8731-4796c86b1097',
            'contacts'    => [
                'oro_dotmailer.contact.add_contact_rejected',
                'oro_dotmailer.contact.update_contact_rejected',
                'oro_dotmailer.contact.update_2'
            ],
            'reference'   => 'oro_dotmailer.address_book_contacts_export.rejected'
        ],
        [
            'status'      => AddressBookContactsExport::STATUS_NOT_FINISHED,
            'addressBook' => 'oro_dotmailer.address_book.fourth',
            'channel'     => 'oro_dotmailer.channel.third',
            'importId'    => '2fb9cba7-e588-445a-8731-4796c86b1097',
            'contacts'    => [],
            'reference'   => 'oro_dotmailer.address_book_contacts_export.second'
        ],
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
            $this->setEntityPropertyValues($addressBookContactExport, $item, ['reference', 'contacts']);

            $manager->persist($addressBookContactExport);

            $this->addReference($item['reference'], $addressBookContactExport);

            if (!empty($item['contacts'])) {
                foreach ($item['contacts'] as $contact) {
                    /** @var Contact $contact */
                    $contact = $this->getReference($contact);
                    $addressBookContacts = $contact->getAddressBookContacts();

                    foreach ($addressBookContacts as $addressBookContact) {
                        if ($addressBookContact->getAddressBook() == $item['addressBook']) {
                            $addressBookContact->setExportId($item['importId']);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData'
        ];
    }
}
