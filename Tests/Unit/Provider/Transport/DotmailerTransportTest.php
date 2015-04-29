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

    public function testGetCampaignsWithoutAddressBooks()
    {
        $iterator = $this->target->getCampaigns([]);
        $this->assertInstanceOf('\EmptyIterator', $iterator);
    }

    public function testGetCampaignsWithAddressBooks()
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
        $resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $this->factory->expects($this->once())
            ->method('createResources')
            ->will($this->returnValue($resource));

        $this->target->init($transport);

        $iterator = $this->target->getCampaigns([0 => ['id' => 15645]]);
        $this->assertInstanceOf(
            'OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator',
            $iterator
        );
    }

    public function testGetContactsWithoutSyncDate()
    {
        $resource = $this->initTransportStub();

        $dateSince = null;

        $contactsList = $this->getMock('\StdClass', ['toArray']);
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));

        $resource->expects($this->once())
            ->method('GetContacts')
            ->with(false, 1000, 0)
            ->will($this->returnValue($contactsList));

        $dateSince = null;
        $iterator = $this->target->getContacts($dateSince);
        $iterator->rewind();
    }

    public function testGetContacts()
    {
        $resource = $this->initTransportStub();

        $dateSince = new \DateTime();

        $contactsList = $this->getMock('\StdClass', ['toArray']);
        $contactsList->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([['id' => 1, 'email' => 'test@test.com']]));

        $resource->expects($this->once())
            ->method('GetContactsModifiedSinceDate')
            ->with($dateSince->format(\DateTime::ISO8601), true, 1000, 0)
            ->will($this->returnValue($contactsList));

        $iterator = $this->target->getContacts($dateSince);
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
