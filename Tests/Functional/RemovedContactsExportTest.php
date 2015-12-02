<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\Int32List;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class RemovedContactsExportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListUnsubscribedData',
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListRemovedData',
            ]
        );
    }

    public function testRemove()
    {
        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');
        $addressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');
        $expectedRemoved = [
            $this->getReference('orocrm_dotmailer.contact.removed'),
            $this->getReference('orocrm_dotmailer.contact.removed_as_unsubscribed'),
            $this->getReference('orocrm_dotmailer.contact.removed_from_marketing_list'),
        ];
        $expectedNotRemoveContact = $this->getReference('orocrm_dotmailer.contact.synced');

        foreach ($expectedRemoved as $contact) {
            /**
             * Check fixtures loaded correctly
             */
            $addressBookContact = $this->managerRegistry
                ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
                ->findOneBy(
                    [
                        'addressBook' => $addressBook,
                        'contact' => $contact
                    ]
                );
            $this->assertNotNull($addressBookContact);
        }

        $importFirstAddressBook = new ApiContactImport();
        $importFirstAddressBook->id = '391da8d7-70f0-405b-98d4-02faa41d499d';
        $importFirstAddressBook->status = AddressBookContactsExport::STATUS_NOT_FINISHED;
        $importSecondAddressBook = new ApiContactImport();
        $importSecondAddressBook->id = '291da8d7-70f0-405b-98d4-02faa41d499d';
        $importSecondAddressBook->status = AddressBookContactsExport::STATUS_NOT_FINISHED;

        $this->resource->expects($this->exactly(2))
            ->method('PostAddressBookContactsImport')
            ->willReturnOnConsecutiveCalls($importFirstAddressBook, $importSecondAddressBook);
        $expectedApiContact = new Int32List(
            array_map(function ($contact) {
                return $contact->getOriginId();
            }, $expectedRemoved)
        );
        $expectedAddressBook = $addressBook->getOriginId();
        $this->resource
            ->expects($this->once())
            ->method('PostAddressBookContactsDelete')
            ->with($expectedAddressBook, $expectedApiContact);
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ExportContactConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");


        foreach ($expectedRemoved as $contact) {
            /**
             * Check removed from db
             */
            $addressBookContact = $this->managerRegistry
                ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
                ->findOneBy(
                    [
                        'addressBook' => $addressBook,
                        'contact' => $contact
                    ]
                );
            $this->assertNull($addressBookContact);
        }

        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(
                [
                    'addressBook' => $addressBook,
                    'contact' => $expectedNotRemoveContact
                ]
            );
        $this->assertNotNull($addressBookContact);
    }
}
