<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\Int32List;

use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
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
        $expectedContact = $this->getReference('orocrm_dotmailer.contact.removed');
        $expectedNotRemoveContact = $this->getReference('orocrm_dotmailer.contact.synced');

        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(
                [
                    'addressBook' => $addressBook,
                    'contact' => $expectedContact
                ]
            );
        $this->assertNotNull($addressBookContact);

        $import = new ApiContactImport();
        $import->id = '391da8d7-70f0-405b-98d4-02faa41d499d';
        $import->status = AddressBookContactsExport::STATUS_NOT_FINISHED;

        $this->resource->expects($this->once())
            ->method('PostAddressBookContactsImport')
            ->will($this->returnValue($import));
        $expectedApiContact = new Int32List(
            [

                $expectedContact->getOriginId()
            ]
        );
        $expectedAddressBook = $addressBook->getOriginId();
        $this->resource
            ->expects($this->once())
            ->method('PostAddressBookContactsDelete')
            ->with($expectedAddressBook, $expectedApiContact);

        $processor = $this->getContainer()->get(ReverseSyncCommand::SYNC_PROCESSOR);
        $processor->process($channel, ContactConnector::TYPE, []);


        $addressBookContact = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->findOneBy(
                [
                    'addressBook' => $addressBook,
                    'contact' => $expectedContact
                ]
            );
        $this->assertNull($addressBookContact);

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
