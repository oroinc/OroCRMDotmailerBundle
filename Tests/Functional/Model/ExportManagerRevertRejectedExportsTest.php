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
class ExportManagerRevertRejectedExportsTest extends AbstractImportExportTestCase
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

    public function testUpdateExportResultsRevertRejectedExports()
    {
        /** @var Channel $channel */
        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');

        $rejectedByWatchDogImportId = $this->getReference('orocrm_dotmailer.address_book_contacts_export.rejected')
            ->getImportId();

        $scheduledForExport = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['scheduledForExport' => true]);
        $this->assertCount(2, $scheduledForExport);

        $this->stubResource();

        $this->target->updateExportResults($channel);

        /** @var AddressBook $expectedAddressBook */
        $expectedAddressBook = $this->getReference('orocrm_dotmailer.address_book.six');
        $this->assertExportStatusUpdated($channel, $rejectedByWatchDogImportId, $expectedAddressBook);
        $this->assertAddressBookContactsHandled($channel, $expectedAddressBook);
    }

    /**
     * @param $channel
     * @param $expectedAddressBook
     */
    protected function assertAddressBookContactsHandled($channel, $expectedAddressBook)
    {
        /**
         * Check New Address Book Contact removed
         * if it was rejected and operation == AddressBookContact::EXPORT_NEW_CONTACT
         */
        $this->assertAddressBookContactNotExist(
            $channel,
            $this->getReference('orocrm_dotmailer.contact.add_contact_rejected'),
            $expectedAddressBook
        );

        /**
         * Check New Address Book Contact removed
         * if it was rejected and operation == AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK
         */
        $this->assertAddressBookContactNotExist(
            $channel,
            $this->getReference('orocrm_dotmailer.contact.update_2'),
            $expectedAddressBook
        );

        /**
         * Check New Address Book Contact was not removed
         * if it was rejected and operation == AddressBookContact::EXPORT_UPDATE_CONTACT
         */
        $this->assertAddressBookContact(
            $channel,
            $this->getReference('orocrm_dotmailer.contact.update_contact_rejected'),
            $expectedAddressBook,
            Contact::STATUS_SUBSCRIBED
        );

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
     * @param Channel $channel
     * @param $importId
     * @param AddressBook $expectedAddressBook
     *
     * @return AddressBook
     */
    protected function assertExportStatusUpdated(Channel $channel, $importId, AddressBook $expectedAddressBook)
    {
        $status = AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG;
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

    protected function stubResource()
    {
        $apiContactImportStatus = new ApiContactImport();
        $apiContactImportStatus->status = ApiContactImportStatuses::FINISHED;

        $this->resource->expects($this->any())
            ->method('GetContactsImportByImportId')
            ->willReturn($apiContactImportStatus);
    }
}
