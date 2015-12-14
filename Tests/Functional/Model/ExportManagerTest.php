<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Model;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiContactImportStatuses;
use DotMailer\Api\DataTypes\ApiContactList;

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

        $rejectedByWatchDogImportId = '5fb9cba7-e588-445a-8731-4796c86b1097';
        $importWithFaultsId = '1fb9cba7-e588-445a-8731-4796c86b1097';

        $scheduledForExport = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['scheduledForExport' => true ]);
        $this->assertCount(2, $scheduledForExport);

        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;

        $this->resource->expects($this->once())
            ->method('GetContactsImportByImportId')
            ->with('1fb9cba7-e588-445a-8731-4796c86b1097')
            ->willReturn($apiContactImportStatus);

        $expectedEmail = 'test2@ex.com';
        $expectedId = 143;
        $entity = new ApiContactList();
        $entity[] = [
            'id'        => $expectedId,
            'email'     => $expectedEmail
        ];
        $this->resource->expects($this->any())
            ->method('GetAddressBookContacts')
            ->will($this->returnValue($entity));


        $this->resource->expects($this->exactly(2))
            ->method('GetContactsImportReportFaults')
            ->withConsecutive([$importWithFaultsId], ['6fb9cba7-e588-445a-8731-4796c86b1097'])
            ->willReturnOnConsecutiveCalls(file_get_contents(__DIR__ . '/Fixtures/importFaults.csv'), '');

        $this->target->updateExportResults($channel);


        $this->assertFinishedExportResultsUpdated($channel, $importWithFaultsId);
        $this->assertRejectedExportResultsUpdated($channel, $rejectedByWatchDogImportId);
    }

    /**
     * @param $channel
     */
    protected function assertFinishedExportResultsUpdated($channel, $importId)
    {
        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');

        $status = AddressBookContactsExport::STATUS_FINISH;
        $this->assertExport($channel, $importId, $expectedAddressBook, $status);

        /**
         * Check not exported contacts properly handled
         * @var Contact $contact
         */
        $contact = $this->getReference('orocrm_dotmailer.contact.update_1');
        $this->assertAddressBookContact($channel, $contact, $expectedAddressBook, Contact::STATUS_SUPPRESSED);
    }

    /**
     * @param Channel $channel
     * @param string  $importId
     */
    protected function assertRejectedExportResultsUpdated(Channel $channel, $importId)
    {
        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('orocrm_dotmailer.address_book.six');

        $status = AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG;
        $this->assertExport($channel, $importId, $expectedAddressBook, $status);

        /**
         * Check Rejected By Watchdog Contacts handled
         * @var Contact $contact
         */
        $contact = $this->getReference('orocrm_dotmailer.contact.second');
        $this->assertAddressBookContactNotExist($channel, $contact, $expectedAddressBook);

        $contact = $this->getReference('orocrm_dotmailer.contact.test_concurrent_statuses');
        $this->assertAddressBookContactNotExist($channel, $contact, $expectedAddressBook);

        /**
         * Check other Address Book AddressBookContact not removed
         */
        $contact = $this->getReference('orocrm_dotmailer.contact.allen_case');
        $this->assertAddressBookContact($channel, $contact, $expectedAddressBook, Contact::STATUS_SUBSCRIBED);
    }

    /**
     * @param Channel     $channel
     * @param Contact     $contact
     * @param AddressBook $addressBook
     * @param string      $status
     */
    protected function assertAddressBookContact(Channel $channel, Contact $contact, AddressBook $addressBook, $status)
    {
        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['contact' => $contact, 'channel' => $channel, 'addressBook' => $addressBook]);

        $this->assertCount(1, $addressBookContact);
        $addressBookContact = reset($addressBookContact);

        $this->assertEquals($status, $addressBookContact->getStatus()->getId());
    }

    /**
     * @param Channel $channel
     * @param Contact $contact
     * @param AddressBook $expectedAddressBook
     */
    protected function assertAddressBookContactNotExist(
        Channel $channel,
        Contact $contact,
        AddressBook $expectedAddressBook
    ) {
        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(['contact' => $contact, 'channel' => $channel, 'addressBook' => $expectedAddressBook]);
        $this->assertNull($addressBookContact);
    }

    /**
     * @param Channel     $channel
     * @param string      $importId
     * @param AddressBook $expectedAddressBook
     * @param string      $status
     */
    protected function assertExport(Channel $channel, $importId, AddressBook $expectedAddressBook, $status)
    {
        $exportEntities = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(['channel' => $channel, 'importId' => $importId]);
        $this->assertCount(1, $exportEntities);

        /** @var AddressBookContactsExport|bool $exportEntity */
        $exportEntity = reset($exportEntities);

        $exportStatus = $exportEntity->getStatus();
        $this->assertEquals($status, $exportStatus->getId());

        $addressBookStatus = $expectedAddressBook->getSyncStatus();
        $this->assertEquals($status, $addressBookStatus->getId());
    }
}
