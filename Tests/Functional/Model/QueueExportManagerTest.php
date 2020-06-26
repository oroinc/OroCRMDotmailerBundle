<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;
use DotMailer\Api\DataTypes\Guid;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\DotmailerBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class QueueExportManagerTest extends AbstractImportExportTestCase
{
    /**
     * @var QueueExportManager
     */
    protected $target;

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                LoadAddressBookContactsExportData::class
            ]
        );
        $this->target = $this->getContainer()->get('oro_dotmailer.queue_export_manager');
    }

    public function testUpdateExportResults()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $importWithFaultsId = $this->getReference('oro_dotmailer.address_book_contacts_export.first')
            ->getImportId();

        $importAddToAddressBook = $this
            ->getReference('oro_dotmailer.address_book_contacts_export.add_to_address_book')
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

        $this->target->updateExportResults($channel);

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $this->assertExportStatusUpdated($channel, $importWithFaultsId, $expectedAddressBook);

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

    /**
     * @param Channel $channel
     * @param string $importId
     * @param AddressBook $expectedAddressBook
     */
    protected function assertExportStatusUpdated(Channel $channel, $importId, AddressBook $expectedAddressBook)
    {
        $exportEntities = $this->managerRegistry->getRepository(AddressBookContactsExport::class)
            ->findBy(['channel' => $channel, 'importId' => $importId]);
        $this->assertCount(1, $exportEntities);

        /** @var AddressBookContactsExport|bool $exportEntity */
        $exportEntity = reset($exportEntities);

        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_FINISH, $exportStatus->getId());

        $addressBookStatus = $expectedAddressBook->getSyncStatus();
        $this->assertEquals(AddressBookContactsExport::STATUS_FINISH, $addressBookStatus->getId());
    }
}
