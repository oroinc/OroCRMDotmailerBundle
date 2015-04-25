<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class DotmailerTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DotmailerTransport
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = $this->getMock(
            'OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory'
        );

        $this->target = new DotmailerTransport(
            $this->factory
        );
    }

    public function testInit()
    {
        $username = 'John';
        $password = '42';
        $transport = $this->getMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->getMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $settingsBag->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, false, $username],
                    ['password', null, false, $password],
                ]
            ));
        $transport->expects($this->once())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));

        $this->factory->expects($this->once())
            ->method('createResources')
            ->with($username, $password);

        $this->target->init($transport);
    }

    /**
     * @expectedException \OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException
     * @expectedExceptionMessage Option "password" is required
     */
    public function testInitThrowAnExceptionIfUsernameOptionsEmpty()
    {
        $transport = $this->getMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->getMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));
        $settingsBag->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, false, 'any not empty username'],
                    ['password', null, false, null],
                ]
            ));

        $this->target->init($transport);
    }

    /**
     * @expectedException \OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException
     * @expectedExceptionMessage Option "username" is required
     */
    public function testInitThrowAnExceptionIfPasswordOptionsEmpty()
    {
        $transport = $this->getMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->getMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));

        $this->target->init($transport);
    }

    public function testGetUnsubscribedFromAccountsContactsWithoutSyncDate()
    {
        $iterator = $this->target->getUnsubscribedFromAccountsContacts();
        $this->assertInstanceOf('\EmptyIterator', $iterator);
    }

    public function testGetUnsubscribedFromAccountsContacts()
    {
        $resource = $this->initTransportStub();

        $expectedDate = new \DateTime();
        $iterator = $this->target->getUnsubscribedFromAccountsContacts($expectedDate);
        /**
         * Test iterator initialized with correct address book origin id and last sync date
         */
        $contactsList = $this->getMock('\StdClass', ['toArray']);
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetContactsUnsubscribedSinceDate')
            ->with($expectedDate)
            ->will($this->returnValue($contactsList));
        $iterator->rewind();
    }

    //GetContactsUnsubscribedSinceDate
    public function testGetUnsubscribedContactsWithoutSyncDate()
    {
        $iterator = $this->target->getUnsubscribedContacts([]);
        $this->assertInstanceOf('\EmptyIterator', $iterator);
    }

    public function testGetUnsubscribedContactsWithoutAddressBook()
    {
        $iterator = $this->target->getCampaigns([]);
        $this->assertInstanceOf('\Iterator', $iterator);

        $this->assertEquals(0, iterator_count($iterator));
    }

    public function testGetUnsubscribedContacts()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $expectedDate = new \DateTime();
        $iterator = $this->target->getUnsubscribedContacts(
            [0 => ['originId' => $expectedAddressBookOriginId]],
            $expectedDate
        );
        $this->assertInstanceOf(
            'Guzzle\Iterator\AppendIterator',
            $iterator
        );

        /**
         * Test iterator initialized with correct address book origin id and last sync date
         */
        $contactsList = $this->getMock('\StdClass', ['toArray']);
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetAddressBookContactsUnsubscribedSinceDate')
            ->with(
                $expectedAddressBookOriginId,
                $expectedDate
            )
            ->will($this->returnValue($contactsList));
        $iterator->rewind();
    }

    public function testGetCampaignsWithoutAddressBooks()
    {
        $iterator = $this->target->getCampaigns([]);
        $this->assertInstanceOf('\Iterator', $iterator);

        $this->assertEquals(0, iterator_count($iterator));
    }

    public function testGetCampaignsWithAddressBooks()
    {
        $resource = $this->initTransportStub();

        $expectedAddressBookOriginId = 15645;
        $iterator = $this->target->getCampaigns([0 => ['originId' => $expectedAddressBookOriginId]]);
        $this->assertInstanceOf(
            'Guzzle\Iterator\AppendIterator',
            $iterator
        );

        /**
         * Test iterator initialized with correct address book origin id
         */
        $campaignsList = $this->getMock('\StdClass', ['toArray']);
        $campaignsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));
        $resource->expects($this->once())
            ->method('GetAddressBookCampaigns')
            ->with($expectedAddressBookOriginId)
            ->will($this->returnValue($campaignsList));
        $iterator->rewind();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     * @throws \OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException
     */
    protected function initTransportStub()
    {
        $username = 'John';
        $password = '42';
        $transport = $this->getMock(
            'Oro\Bundle\IntegrationBundle\Entity\Transport'
        );
        $settingsBag = $this->getMock(
            'Symfony\Component\HttpFoundation\ParameterBag'
        );
        $settingsBag->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['username', null, false, $username],
                    ['password', null, false, $password],
                ]
            ));
        $transport->expects($this->any())
            ->method('getSettingsBag')
            ->will($this->returnValue($settingsBag));
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $this->factory->expects($this->any())
            ->method('createResources')
            ->will($this->returnValue($resource));

        $this->target->init($transport);
        return $resource;
    }
}
