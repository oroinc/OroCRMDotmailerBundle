<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;

class ExportContactsByAddressBookTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldMappingData'
            ]
        );
    }

    public function testExport()
    {
        $channel = $this->getReference('oro_dotmailer.channel.fourth');
        $firstAddressBook = $this->getReference('oro_dotmailer.address_book.fifth');
        $firstAddressBookImportStatus = $this->getImportStatus(
            $firstAddressBookId = '391da8d7-70f0-405b-98d4-02faa41d499d',
            AddressBookContactsExport::STATUS_NOT_FINISHED
        );
        $expectedAddressBookMap = [
            (int)$firstAddressBook->getOriginId()  => $firstAddressBookImportStatus,
        ];
        $this->resource->expects($this->any())
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
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $firstAddressBook->getId()
            ]
        );

        $this->assertTrue($result);
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
}
