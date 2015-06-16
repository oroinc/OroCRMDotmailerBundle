<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact as DotmailerContact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class ExportContactsTest extends AbstractImportExportTestCase
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

        $expectedId = '391da8d7-70f0-405b-98d4-02faa41d499d';
        $statusClass = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $expectedStatus = $this->managerRegistry
            ->getRepository($statusClass)
            ->find(AddressBookContactsExport::STATUS_NOT_FINISHED);
        $import = new ApiContactImport();
        $import->id = $expectedId;
        $import->status = AddressBookContactsExport::STATUS_NOT_FINISHED;
        $this->resource->expects($this->once())
            ->method('PostAddressBookContactsImport')
            ->with($addressBook->getOriginId())
            ->will($this->returnValue($import));

        $processor = $this->getContainer()->get(ReverseSyncCommand::SYNC_PROCESSOR);
        $processor->process($channel, ContactConnector::TYPE, []);

        /**
         * Check new contact exported correctly
         */
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

        /**
         * Check existing contact exported correctly
         */
        $contact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['channel' => $channel, 'email' => 'alex.case@example.com']);
        $this->assertNotNull($contact, 'Updated contact not synced');
        $this->assertContactUpdated(
            $contact,
            $this->getReference('orocrm_dotmailer.orocrm_contact.alex.case'),
            $addressBook
        );

        /**
         * Check existing contact exported correctly
         */
        $contact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['channel' => $channel, 'email' => 'allen.case@example.com']);
        $this->assertNotNull($contact, 'Updated contact not synced');
        $this->assertContactUpdated(
            $contact,
            $this->getReference('orocrm_dotmailer.orocrm_contact.allen.case'),
            $addressBook
        );

        $export = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(
                [
                    'addressBook' => $addressBook,
                    'importId' => $expectedId,
                    'status' => $expectedStatus
                ]
            );
        $this->assertCount(1, $export);
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

        if (!$isNew) {
            /**
             * This is necessary to not update information fields for existing contacts
             */
            $this->assertNotEquals($expected->getFirstName(), $actual->getFirstName());
            $this->assertNotEquals($expected->getLastName(), $actual->getLastName());
        } else {
            $this->assertEquals($expected->getFirstName(), $actual->getFirstName());
            $this->assertEquals($expected->getLastName(), $actual->getLastName());
        }
    }
}
