<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact as DotmailerContact;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;

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
        $firstAddressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');
        $firstAddressBookImportStatus = $this->getImportStatus(
            $firstAddressBookId = '391da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_NOT_FINISHED
        );
        $statusClass = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->managerRegistry->getRepository($statusClass);
        $firstAddressBookStatusEnum = $statusRepository->find(AddressBookContactsExport::STATUS_NOT_FINISHED);
        $secondAddressBook = $this->getReference('orocrm_dotmailer.address_book.six');
        $secondAddressBookImportStatus = $this->getImportStatus(
            $secondAddressBookId = '451da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_FINISH
        );
        $secondAddressBookStatusEnum = $statusRepository->find(AddressBookContactsExport::STATUS_FINISH);

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
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

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
            $firstAddressBook,
            true
        );

        /**
         * Check new contact exported correctly for both address books
         */
        $this->assertContactUpdated(
            $contact,
            $this->getReference('orocrm_dotmailer.orocrm_contact.jack.case'),
            $secondAddressBook,
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
            $firstAddressBook
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
            $firstAddressBook
        );

        $this->assertAddressBookExportStatus($firstAddressBook, $firstAddressBookId, $firstAddressBookStatusEnum);
        $this->assertAddressBookExportStatus($secondAddressBook, $secondAddressBookId, $secondAddressBookStatusEnum);
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
        /**
         * Check status was reset after export
         */
        $this->assertFalse($addressBookContact->isScheduledForExport());

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

    /**
     * @param AddressBook $addressBook
     * @param string $importId
     * @param AbstractEnumValue $status
     */
    protected function assertAddressBookExportStatus(AddressBook $addressBook, $importId, AbstractEnumValue $status)
    {
        $export = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->findBy(
                [
                    'addressBook' => $addressBook,
                    'importId'    => $importId,
                    'status'      => $status
                ]
            );
        $this->assertCount(1, $export);
    }
}
