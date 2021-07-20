<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ExportContactsTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldMappingData'
            ]
        );
    }

    public function testSync()
    {
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $previousNotExportedContact = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Contact')
            ->findOneBy(['email' => 'test2@ex.com']);
        $this->assertNotNull($previousNotExportedContact);

        $firstAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $firstAddressBookImportStatus = $this->getImportStatus(
            $firstAddressBookId = '391da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_NOT_FINISHED
        );

        $secondAddressBook = $this->getReference('oro_dotmailer.address_book.six');
        $secondAddressBookImportStatus = $this->getImportStatus(
            $secondAddressBookId = '451da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_FINISH
        );

        $expectedAddressBookMap = [
            (int)$firstAddressBook->getOriginId()  => $firstAddressBookImportStatus,
            (int)$secondAddressBook->getOriginId() => $secondAddressBookImportStatus,
        ];

        $this->resource->expects($this->exactly(2))
            ->method('PostAddressBookContactsImport')
            ->willReturnCallback(function ($originId) use ($expectedAddressBookMap) {
                $this->assertArrayHasKey(
                    (int)$originId,
                    $expectedAddressBookMap,
                    "Unexpected AddressBook origin Id $originId"
                );

                return $expectedAddressBookMap[$originId];
            });

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ExportContactConnector::TYPE,
            [],
            $jobLog
        );

        $firstAddressBook = $this->refreshAddressBook($firstAddressBook);
        $secondAddressBook = $this->refreshAddressBook($secondAddressBook);
        $upToDateAddressBook = $this->refreshAddressBook(
            $this->getReference('oro_dotmailer.address_book.up_to_date')
        );

        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $expectedContact = $this->getReference('oro_dotmailer.orocrm_contact.jack.case');

        /**
         * Check new contact exported correctly
         */
        $this->assertContactUpdated($channel, 'jack.case@example.com', $expectedContact, $firstAddressBook, true);

        /**
         * Check new contact exported correctly for both address books
         */
        $this->assertContactUpdated($channel, 'jack.case@example.com', $expectedContact, $secondAddressBook, true);

        /**
         * Check existing contact exported correctly
         */
        $expectedContact = $this->getReference('oro_dotmailer.orocrm_contact.alex.case');
        $this->assertContactUpdated($channel, 'alex.case@example.com', $expectedContact, $firstAddressBook);

        /**
         * Check existing contact exported correctly
         */
        $expectedContact = $this->getReference('oro_dotmailer.orocrm_contact.allen.case');
        $this->assertContactUpdated($channel, 'allen.case@example.com', $expectedContact, $firstAddressBook);

        $statusClass = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->managerRegistry->getRepository($statusClass);
        $syncInProgressStatus = $statusRepository->find(AddressBookContactsExport::STATUS_NOT_FINISHED);
        $syncFinishedStatus = $statusRepository->find(AddressBookContactsExport::STATUS_FINISH);

        $this->assertEquals($syncFinishedStatus, $upToDateAddressBook->getSyncStatus());
        $this->assertAddressBookExportStatus($firstAddressBook, $firstAddressBookId, $syncInProgressStatus);
        $this->assertAddressBookExportStatus($secondAddressBook, $secondAddressBookId, $syncFinishedStatus);

        /**
         * Check previous not exported contact was removed before new export start
         */
        $previousNotExportedContact = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Contact')
            ->findOneBy(['email' => 'test2@ex.com']);
        $this->assertNull($previousNotExportedContact);
    }

    /**
     * @param string $id GUID
     * @param string $status
     *
     * @return ApiContactImport
     */
    protected function getImportStatus($id, $status)
    {
        $addressBookImportStatus = new ApiContactImport();
        $addressBookImportStatus->id = $id;
        $addressBookImportStatus->status = $status;

        return $addressBookImportStatus;
    }

    /**
     * @param Channel     $channel
     * @param string      $email
     * @param Contact     $expected
     * @param AddressBook $addressBook
     * @param bool        $isNew
     */
    protected function assertContactUpdated(
        Channel $channel,
        $email,
        Contact $expected,
        AddressBook $addressBook,
        $isNew = false
    ) {
        $actual = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Contact')
            ->findOneBy(['channel' => $channel, 'email' => $email]);
        $this->assertNotNull($actual, 'Updated contact not synced');

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
        /**
         * Check status was reset after export
         */
        $this->assertFalse($addressBookContact->isScheduledForExport());

        $dataFields = $actual->getDataFields();
        static::assertEquals($dataFields['FIRSTNAME'], $expected->getFirstName());
        static::assertEquals($dataFields['LASTNAME'], $expected->getLastName());
    }

    /**
     * @param AddressBook $addressBook
     * @param string $importId
     * @param AbstractEnumValue $status
     */
    protected function assertAddressBookExportStatus(AddressBook $addressBook, $importId, AbstractEnumValue $status)
    {
        $export = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBookContactsExport')
            ->findBy(
                [
                    'addressBook' => $addressBook,
                    'importId'    => $importId,
                    'status'      => $status
                ]
            );

        $this->assertCount(1, $export);
        $this->assertEquals($status, $addressBook->getSyncStatus());
    }

    protected function refreshAddressBook(AddressBook $addressBook)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->find($addressBook->getId());
    }
}
