<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiAddressBook;
use DotMailer\Api\DataTypes\ApiAddressBookList;

use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class RemoveAddressBookImportTest extends AbstractImportExportTest
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

        $processor = $this->getContainer()->get(SyncCommand::SYNC_PROCESSOR);
        $result = $processor->process($channel, AddressBookConnector::TYPE);

        $this->assertTrue($result);

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
