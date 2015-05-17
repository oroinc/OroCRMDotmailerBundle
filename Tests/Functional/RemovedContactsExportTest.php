<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\Int32List;
use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
class RemovedContactsExportTest extends AbstractImportExportTest
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

    public function testRemove()
    {
        $channel = $this->getReference('orocrm_dotmailer.channel.fourth');
        $addressBook = $this->getReference('orocrm_dotmailer.address_book.fifth');

        $addressBookContacts = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['addressBook' => $addressBook]);
        $this->assertCount(3, $addressBookContacts);

        $expectedContact = new Int32List(
            [
                $this->getReference('orocrm_dotmailer.contact.removed')
                    ->getOriginId()
            ]
        );
        $expectedAddressBook = $addressBook->getOriginId();
        $this->resource
            ->expects($this->once())
            ->method('PostAddressBookContactsDelete')
            ->with($expectedAddressBook, $expectedContact);

        $processor = $this->getContainer()->get(ReverseSyncCommand::SYNC_PROCESSOR);
        $processor->process($channel, ContactConnector::TYPE, []);

        $addressBookContacts = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findBy(['addressBook' => $addressBook]);
        $this->assertCount(2, $addressBookContacts);
    }
}
