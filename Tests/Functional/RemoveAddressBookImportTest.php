<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;

/**
 * @dbIsolation
 */
class RemoveAddressBookImportTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData'
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
        $channel = $this->getReference('orocrm_dotmailer.channel.first');

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
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findBy(['originId' => $expectedPresentedAddressBookId]);

        $this->assertCount(1, $addressBook, 'Address Book must be presented');

        $addressBook = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findBy(['originId' => $expectedRemovedAddressBookId]);

        $this->assertCount(0, $addressBook, 'Address Book must be removed');
    }
}
