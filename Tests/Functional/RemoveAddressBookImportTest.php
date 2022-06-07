<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;

class RemoveAddressBookImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAddressBookData::class]);
    }

    public function testImport()
    {
        $entity = new ApiAddressBookList();
        $expectedPresentedAddressBookId = 11;
        $expectedRemovedAddressBookId = 12;
        $entity[] = new ApiAddressBook(['Id' => $expectedPresentedAddressBookId, 'Name' => 'test1']);
        $this->resource->expects($this->any())
            ->method('GetAddressBooks')
            ->willReturn($entity);
        $channel = $this->getReference('oro_dotmailer.channel.first');

        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            AddressBookConnector::TYPE,
            [],
            $jobLog
        );
        $log = $this->formatImportExportJobLog($jobLog);
        $this->assertTrue($result, "Job Failed with output:\n $log");

        $addressBook = $this->managerRegistry
            ->getRepository(AddressBook::class)
            ->findBy(['originId' => $expectedPresentedAddressBookId]);

        $this->assertCount(1, $addressBook, 'Address Book must be presented');

        $addressBook = $this->managerRegistry
            ->getRepository(AddressBook::class)
            ->findBy(['originId' => $expectedRemovedAddressBookId]);

        $this->assertCount(0, $addressBook, 'Address Book must be removed');
    }
}
