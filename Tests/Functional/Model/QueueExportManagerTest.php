<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;
use DotMailer\Api\DataTypes\Guid;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\DotmailerBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * @dbIsolationPerTest
 */
class QueueExportManagerTest extends AbstractImportExportTestCase
{
    private QueueExportManager $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAddressBookContactsExportData::class]);

        $this->target = $this->getContainer()->get('oro_dotmailer.queue_export_manager');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Rollback settings
        $this->target->setTotalErroneousAttempts(10);
        $this->target->setTotalNotFinishedAttempts(30);
    }

    public function testUpdateExportResults()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $importWithFaultsId = $this->getReference('oro_dotmailer.address_book_contacts_export.first')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository(AddressBookContact::class)
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(3, $scheduledForExport);

        // Expect Dotmailer will return FINISHED status for import with id=$importWithFaultsId which was NOT_FINISHED
        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;
        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with($importWithFaultsId)
            ->willReturn($apiContactImportStatus);

        $this->resource->expects($this->exactly(2))
            ->method('GetContactsImportReportFaults')
            ->willReturnCallback(static function (Guid $id) use ($importWithFaultsId) {
                if ((string)$id === $importWithFaultsId) {
                    return file_get_contents(__DIR__ . '/Fixtures/importFaults.csv');
                }

                return '';
            });

        $this->assertTrue($this->target->updateExportResults($channel));

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $exportEntity = $this->getExportEntity($channel, $importWithFaultsId);
        $this->assertEmpty($exportEntity->getSyncAttempts());
        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_FINISH, $exportStatus->getId());

        $addressBookStatus = $expectedAddressBook->getSyncStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_FINISH, $addressBookStatus->getId());

        /**
         * Check not exported contacts properly handled
         */
        $addressBookContacts = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findBy(
                [
                    'contact' => $this->getReference('oro_dotmailer.contact.update_1'),
                    'channel' => $channel,
                    'addressBook' => $expectedAddressBook
                ]
            );

        $this->assertCount(1, $addressBookContacts);
        $addressBookContact = reset($addressBookContacts);

        $this->assertEquals(Contact::STATUS_SUPPRESSED, $addressBookContact->getStatus()->getId());
    }

    public function testUpdateExportResultsWithUnknownStatus()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $importWithFaultsId = $this->getReference('oro_dotmailer.address_book_contacts_export.first')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository(AddressBookContact::class)
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(3, $scheduledForExport);

        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = null;
        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with($importWithFaultsId)
            ->willReturn($apiContactImportStatus);

        $this->resource->expects($this->exactly(2))
            ->method('GetContactsImportReportFaults')
            ->willReturnCallback(static function (Guid $id) use ($importWithFaultsId) {
                if ((string)$id === $importWithFaultsId) {
                    return file_get_contents(__DIR__ . '/Fixtures/importFaults.csv');
                }

                return '';
            });

        $this->assertFalse($this->target->updateExportResults($channel));

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $exportEntity = $this->getExportEntity($channel, $importWithFaultsId);
        $this->assertEquals(1, $exportEntity->getSyncAttempts());
        $this->assertTrue($exportEntity->isFaultsProcessed());
        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_NOT_FINISHED, $exportStatus->getId());

        $addressBookStatus = $expectedAddressBook->getSyncStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_NOT_FINISHED, $addressBookStatus->getId());

        /**
         * Check not exported contacts properly handled
         */
        $addressBookContacts = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findBy(
                [
                    'contact' => $this->getReference('oro_dotmailer.contact.update_1'),
                    'channel' => $channel,
                    'addressBook' => $expectedAddressBook
                ]
            );

        $this->assertCount(1, $addressBookContacts);
        $addressBookContact = reset($addressBookContacts);

        $this->assertEquals(Contact::STATUS_SUPPRESSED, $addressBookContact->getStatus()->getId());
    }

    public function testUpdateExportResultsWithErrorThrown()
    {
        $this->target->setTotalErroneousAttempts(1);
        $this->target->setTotalNotFinishedAttempts(1);

        /** @var Channel $channel */
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $importWithFaultsId = $this->getReference('oro_dotmailer.address_book_contacts_export.first')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository(AddressBookContact::class)
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(3, $scheduledForExport);

        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = null;
        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with($importWithFaultsId)
            ->willThrowException(new RestClientException());

        $this->assertFalse($this->target->updateExportResults($channel));

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $exportEntity = $this->getExportEntity($channel, $importWithFaultsId);
        $this->assertEquals(1, $exportEntity->getSyncAttempts());
        $this->assertTrue($exportEntity->isFaultsProcessed());
        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_UNKNOWN, $exportStatus->getId());

        /**
         * Check not exported contacts properly handled
         */
        $addressBookContacts = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findBy(
                [
                    'contact' => $this->getReference('oro_dotmailer.contact.update_1'),
                    'channel' => $channel,
                    'addressBook' => $expectedAddressBook
                ]
            );

        $this->assertCount(1, $addressBookContacts);
        $addressBookContact = reset($addressBookContacts);

        $this->assertEquals(Contact::STATUS_SUBSCRIBED, $addressBookContact->getStatus()->getId());
    }

    protected function getExportEntity(Channel $channel, string $importId): AddressBookContactsExport
    {
        $exportEntities = $this->managerRegistry->getRepository(AddressBookContactsExport::class)
            ->findBy(['channel' => $channel, 'importId' => $importId]);
        $this->assertCount(1, $exportEntities);

        return reset($exportEntities);
    }
}
