<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;

/**
 * @dbIsolation
 */
class ExportSyncsUnsubscribedDMContactsWithMarketingListItemsTest extends AbstractImportExportTestCase
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

        $unsubscribedItem = $this->managerRegistry
            ->getRepository('OroCRMMarketingListBundle:MarketingListUnsubscribedItem')
            ->findOneBy(
                [
                    'marketingList' => $this->getReference('orocrm_dotmailer.marketing_list.fifth')->getId(),
                    'entityId' => $this->getReference('orocrm_dotmailer.orocrm_contact.daniel.case')->getId()
                ]
            );
        $this->managerRegistry->getManager()->remove($unsubscribedItem);
        $this->managerRegistry->getManager()->flush();

        $previousNotExportedContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->findOneBy(['email' => 'test2@ex.com']);
        $this->assertNotNull($previousNotExportedContact);

        $firstAddressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');
        $firstAddressBookImportStatus = $this->getImportStatus(
            $firstAddressBookId = '391da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_NOT_FINISHED
        );

        $secondAddressBook = $this->getReference('orocrm_dotmailer.address_book.six');
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

        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $unsubscribedItem = $this->managerRegistry
            ->getRepository('OroCRMMarketingListBundle:MarketingListUnsubscribedItem')
            ->findOneBy(
                [
                    'marketingList' => $this->getReference('orocrm_dotmailer.marketing_list.fifth')->getId(),
                    'entityId' => $this->getReference('orocrm_dotmailer.orocrm_contact.daniel.case')->getId()
                ]
            );

        $this->assertNotNull($unsubscribedItem);
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

    protected function refreshAddressBook(AddressBook $addressBook)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->find($addressBook->getId());
    }
}
