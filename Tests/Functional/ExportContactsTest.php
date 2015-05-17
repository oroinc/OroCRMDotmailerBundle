<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact as DotmailerContact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ExportContactsTest extends AbstractImportExportTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
            ]
        );
    }

    public function testSync()
    {
        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');
        $addressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');

        $processor = $this->getContainer()->get(ReverseSyncCommand::SYNC_PROCESSOR);
        $processor->process($channel, ContactConnector::TYPE, []);

        $this->assertContactUpdated(
            $this->getReference('orocrm_dotmailer.contact.exported'),
            $this->getReference('orocrm_dotmailer.orocrm_contact.john.case'),
            $addressBook
        );

        $contact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['channel' => $channel, 'email' => 'jack.case@example.com']);
        $this->assertNotNull($contact, 'New contact not synced');
        $this->assertContactUpdated(
            $contact,
            $this->getReference('orocrm_dotmailer.orocrm_contact.jack.case'),
            $addressBook,
            true
        );
    }

    /**
     * @param DotmailerContact $actual
     * @param Contact          $expected
     * @param AddressBook      $addressBook
     * @param bool             $isNew
     */
    protected function assertContactUpdated(
        DotmailerContact $actual,
        Contact $expected,
        AddressBook $addressBook,
        $isNew = false
    ) {
        if ($isNew) {
            $this->assertNull($actual->getOriginId());
        } else {
            $this->assertNotEmpty($actual->getOriginId());
        }
        /** @var AddressBookContact $addressBookContact */
        $addressBookContact = $actual->getAddressBookContacts()
            ->filter(function (AddressBookContact $addressBookContact) use ($addressBook) {
                $id = $addressBookContact->getAddressBook()->getId();
                return $id == $addressBook->getId();
            })
            ->first();
        $this->assertTrue($addressBookContact->isScheduledForExport());

        $this->assertEquals($expected->getFirstName(), $actual->getFirstName());
        $this->assertEquals($expected->getLastName(), $actual->getLastName());
    }
}
