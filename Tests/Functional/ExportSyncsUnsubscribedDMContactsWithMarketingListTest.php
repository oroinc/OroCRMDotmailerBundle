<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;

class ExportSyncsUnsubscribedDMContactsWithMarketingListTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadDotmailerContactData::class]);
    }

    public function testSync()
    {
        $channel = $this->getReference('oro_dotmailer.channel.fourth');

        $unsubscribedItem = $this->managerRegistry
            ->getRepository(MarketingListUnsubscribedItem::class)
            ->findOneBy([
                'marketingList' => $this->getReference('oro_dotmailer.marketing_list.fifth')->getId(),
                'entityId' => $this->getReference('oro_dotmailer.orocrm_contact.daniel.case')->getId()
            ]);
        $this->managerRegistry->getManager()->remove($unsubscribedItem);
        $this->managerRegistry->getManager()->flush();

        $previousNotExportedContact = $this->managerRegistry
            ->getRepository(Contact::class)
            ->findOneBy(['email' => 'test2@ex.com']);
        $this->assertNotNull($previousNotExportedContact);

        $firstAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $firstAddressBookImportStatus = $this->getImportStatus(
            '391da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_NOT_FINISHED
        );

        $secondAddressBook = $this->getReference('oro_dotmailer.address_book.six');
        $secondAddressBookImportStatus = $this->getImportStatus(
            '451da8d7-70f0-405b-98d4-02faa41d499d',
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

        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $unsubscribedItem = $this->managerRegistry
            ->getRepository(MarketingListUnsubscribedItem::class)
            ->findOneBy([
                'marketingList' => $this->getReference('oro_dotmailer.marketing_list.fifth')->getId(),
                'entityId' => $this->getReference('oro_dotmailer.orocrm_contact.daniel.case')->getId()
            ]);

        $this->assertNotNull($unsubscribedItem);
    }

    private function getImportStatus(string $id, string $status): ApiContactImport
    {
        $addressBookImportStatus = new ApiContactImport();
        $addressBookImportStatus->id = $id;
        $addressBookImportStatus->status = $status;

        return $addressBookImportStatus;
    }
}
