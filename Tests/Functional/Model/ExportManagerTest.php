<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Tests\Functional\AbstractImportExportTestCase;

/**
 * @dbIsolation
 */
class ExportManagerTest extends AbstractImportExportTestCase
{
    /**
     * @var ExportManager
     */
    protected $target;

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookContactsExportData',
            ]
        );
        $this->target = $this->getContainer()->get('orocrm_dotmailer.export_manager');
    }

    public function testUpdateExportResults()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');

        $importWithFaultsId = $this->getReference('orocrm_dotmailer.address_book_contacts_export.first')
            ->getImportId();

        $importAddToAddressBook = $this
            ->getReference('orocrm_dotmailer.address_book_contacts_export.add_to_address_book')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(2, $scheduledForExport);

        // Expect Dotmailer will return FINISHED status for import with id=$importWithFaultsId which was NOT_FINISHED
        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;
        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with($importWithFaultsId)
            ->willReturn($apiContactImportStatus);

        $this->resource->expects($this->exactly(2))
            ->method('GetContactsImportReportFaults')
            ->withConsecutive([$importWithFaultsId], [$importAddToAddressBook])
            ->willReturnOnConsecutiveCalls(file_get_contents(__DIR__ . '/Fixtures/importFaults.csv'), '');

        $this->target->updateExportResults($channel);

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');
        $this->assertExportStatusUpdated($channel, $importWithFaultsId, $expectedAddressBook);

        /**
         * Check not exported contacts properly handled
         */
        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(
                [
                    'contact' => $this->getReference('orocrm_dotmailer.contact.update_1'),
                    'channel' => $channel,
                    'addressBook' => $expectedAddressBook
                ]
            );

        $this->assertCount(1, $addressBookContact);
        $addressBookContact = reset($addressBookContact);

        $this->assertEquals(Contact::STATUS_SUPPRESSED, $addressBookContact->getStatus()->getId());
    }

    /**
     * @param Channel     $channel
     * @param string      $importId
     * @param AddressBook $expectedAddressBook
     */
    protected function assertExportStatusUpdated(Channel $channel, $importId, AddressBook $expectedAddressBook)
    {
        $exportEntities = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
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
