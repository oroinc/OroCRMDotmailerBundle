<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;

class RemoveAddressBookImportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData'
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiAddressBookList();
        $expectedPresentedAddressBookId = 11;
        $expectedRemovedAddressBookId = 12;
        $entity[] = new ApiAddressBook(['Id' => $expectedPresentedAddressBookId, 'Name' => 'test1']);
        $this->resource->expects($this->any())
            ->method('GetAddressBooks')
            ->will($this->returnValue($entity));
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
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->findBy(['originId' => $expectedPresentedAddressBookId]);

        $this->assertCount(1, $addressBook, 'Address Book must be presented');

        $addressBook = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->findBy(['originId' => $expectedRemovedAddressBookId]);

        $this->assertCount(0, $addressBook, 'Address Book must be removed');
    }
}
