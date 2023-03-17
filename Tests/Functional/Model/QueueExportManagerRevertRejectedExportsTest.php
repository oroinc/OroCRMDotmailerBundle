<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\DotmailerBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class QueueExportManagerRevertRejectedExportsTest extends AbstractImportExportTestCase
{
    private QueueExportManager $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAddressBookContactsExportData::class]);

        $this->target = $this->getContainer()->get('oro_dotmailer.queue_export_manager');
    }

    public function testUpdateExportResultsRevertRejectedExports()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $rejectedByWatchDogImportId = $this->getReference('oro_dotmailer.address_book_contacts_export.rejected')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository(AddressBookContact::class)
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(3, $scheduledForExport);

        $this->stubResource();

        $this->target->updateExportResults($channel);

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.six');
        $this->assertExportStatusUpdated($channel, $rejectedByWatchDogImportId, $expectedAddressBook);
        $this->assertAddressBookContactsHandled($channel, $expectedAddressBook);
    }

    private function assertAddressBookContactsHandled(Channel $channel, AddressBook $expectedAddressBook): void
    {
        /**
         * Check New Address Book Contact removed
         * if it was rejected and operation == AddressBookContact::EXPORT_NEW_CONTACT
         */
        $this->assertAddressBookContactNotExist(
            $channel,
            $this->getReference('oro_dotmailer.contact.add_contact_rejected'),
            $expectedAddressBook
        );

        /**
         * Check New Address Book Contact removed
         * if it was rejected and operation == AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK
         */
        $this->assertAddressBookContactNotExist(
            $channel,
            $this->getReference('oro_dotmailer.contact.update_2'),
            $expectedAddressBook
        );

        /**
         * Check New Address Book Contact was not removed
         * if it was rejected and operation == AddressBookContact::EXPORT_UPDATE_CONTACT
         */
        $this->assertAddressBookContact(
            $channel,
            $this->getReference('oro_dotmailer.contact.update_contact_rejected'),
            $expectedAddressBook,
            Contact::STATUS_SUBSCRIBED
        );

        /**
         * Check other Address Book AddressBookContact not removed
         */
        $contact = $this->getReference('oro_dotmailer.contact.allen_case');
        $this->assertAddressBookContact($channel, $contact, $expectedAddressBook, Contact::STATUS_SUBSCRIBED);
    }

    private function assertAddressBookContact(
        Channel $channel,
        Contact $contact,
        AddressBook $addressBook,
        string $status
    ): void {
        $addressBookContact = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findBy(['contact' => $contact, 'channel' => $channel, 'addressBook' => $addressBook]);

        $this->assertCount(1, $addressBookContact);
        $addressBookContact = reset($addressBookContact);

        $this->assertEquals($status, $addressBookContact->getStatus()->getId());
    }

    private function assertAddressBookContactNotExist(
        Channel $channel,
        Contact $contact,
        AddressBook $expectedAddressBook
    ): void {
        $addressBookContact = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findOneBy(['contact' => $contact, 'channel' => $channel, 'addressBook' => $expectedAddressBook]);
        $this->assertNull($addressBookContact);
    }

    private function assertExportStatusUpdated(
        Channel $channel,
        string $importId,
        AddressBook $expectedAddressBook
    ): void {
        $status = AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG;
        $exportEntities = $this->managerRegistry->getRepository(AddressBookContactsExport::class)
            ->findBy(['channel' => $channel, 'importId' => $importId]);
        $this->assertCount(1, $exportEntities);

        /** @var AddressBookContactsExport|bool $exportEntity */
        $exportEntity = reset($exportEntities);

        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals($status, $exportStatus->getId());

        $addressBookStatus = $expectedAddressBook->getSyncStatus();
        $this->assertEquals($status, $addressBookStatus->getId());
    }

    private function stubResource(): void
    {
        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;

        $this->resource->expects($this->any())
            ->method('GetContactsImportByImportId')
            ->willReturn($apiContactImportStatus);
    }
}
