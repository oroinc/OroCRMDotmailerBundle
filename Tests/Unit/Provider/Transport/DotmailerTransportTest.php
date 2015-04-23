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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    protected function setUp()
    {
        $this->factory = $this->getMock(
            'OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory'
        );

        $this->manager = $this->getMock(
            'Doctrine\Common\Persistence\ManagerRegistry'
        );

        $this->target = new DotmailerTransport(
            $this->factory,
            $this->manager
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
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $repository = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository')
            ->disableOriginalConstructor()->getMock();
        $aBooksToSynchronize = [];

        $repository->expects($this->once())
            ->method('getAddressBooksToSync')
            ->will($this->returnValue($aBooksToSynchronize));
        $this->manager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $iterator = $this->target->getCampaigns($channel);
        $this->assertInstanceOf('\ArrayIterator', $iterator);
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

        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $repository = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository')
            ->disableOriginalConstructor()->getMock();
        $aBooksToSynchronize = [0 => ['id' => 15645]];

        $repository->expects($this->once())
            ->method('getAddressBooksToSync')
            ->will($this->returnValue($aBooksToSynchronize));
        $this->manager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $iterator = $this->target->getCampaigns($channel);
        $this->assertInstanceOf(
            'OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator',
            $iterator
        );
    }
}
