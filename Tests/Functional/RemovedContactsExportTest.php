<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\Int32List;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListRemovedData;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListUnsubscribedData;

class RemovedContactsExportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadMarketingListUnsubscribedData::class, LoadMarketingListRemovedData::class]);
    }

    public function testRemove()
    {
        $channel = $this->getReference('oro_dotmailer.channel.fourth');
        $addressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $expectedRemoved = [
            $this->getReference('oro_dotmailer.contact.removed'),
            $this->getReference('oro_dotmailer.contact.removed_as_unsubscribed'),
            $this->getReference('oro_dotmailer.contact.removed_from_marketing_list'),
        ];
        $expectedNotRemoveContact = $this->getReference('oro_dotmailer.contact.synced');

        foreach ($expectedRemoved as $contact) {
            /**
             * Check fixtures loaded correctly
             */
            $addressBookContact = $this->managerRegistry
                ->getRepository(AddressBookContact::class)
                ->findOneBy(['addressBook' => $addressBook, 'contact' => $contact]);
            $this->assertNotNull($addressBookContact);
        }

        $import = new ApiContactImport();
        $import->id = '391da8d7-70f0-405b-98d4-02faa41d499d';
        $import->status = AddressBookContactsExport::STATUS_NOT_FINISHED;

        $import2 = new ApiContactImport();
        $import2->id = '451da8d7-70f0-405b-98d4-02faa41d499d';
        $import2->status = AddressBookContactsExport::STATUS_NOT_FINISHED;

        $this->resource->expects($this->exactly(2))
            ->method('PostAddressBookContactsImport')
            ->will($this->onConsecutiveCalls($import, $import2));

        $expectedApiContact = new Int32List(
            array_map(function ($contact) {
                return $contact->getOriginId();
            }, $expectedRemoved)
        );
        $expectedAddressBook = $addressBook->getOriginId();
        $this->resource->expects($this->once())
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
                ->getRepository(AddressBookContact::class)
                ->findOneBy(['addressBook' => $addressBook, 'contact' => $contact]);
            $this->assertNull($addressBookContact);
        }

        $addressBookContact = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->findOneBy(['addressBook' => $addressBook, 'contact' => $expectedNotRemoveContact]);
        $this->assertNotNull($addressBookContact);
    }
}
