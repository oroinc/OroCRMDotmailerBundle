<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\DotmailerBundle\EventListener\NavigationListener;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var NavigationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->listener = new NavigationListener($this->registry);
    }

    public function testOnNavigationConfigureHasDMIntegrations()
    {
        $channelRepository = $this->createMock(ChannelRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($channelRepository);
        $channelRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => ChannelType::TYPE])
            ->willReturn(['integration']);
        $factory = new MenuFactory();
        $menu = new MenuItem('test_menu', $factory);
        $marketingTab = new MenuItem('marketing_tab', $factory);
        $dmTab = new MenuItem('oro_dotmailer', $factory);
        $marketingTab->addChild($dmTab)->setDisplay(true);
        $menu->addChild($marketingTab);

        $eventData = new ConfigureMenuEvent($factory, $menu);
        $this->listener->onNavigationConfigure($eventData);
        $this->assertTrue($dmTab->isDisplayed());
    }

    public function testOnNavigationConfigureNoDMIntegrations()
    {
        $channelRepository = $this->createMock(ChannelRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($channelRepository);
        $channelRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => ChannelType::TYPE])
            ->willReturn([]);
        $factory = new MenuFactory();
        $menu = new MenuItem('test_menu', $factory);
        $marketingTab = new MenuItem('marketing_tab', $factory);
        $dmTab = new MenuItem('oro_dotmailer', $factory);
        $marketingTab->addChild($dmTab)->setDisplay(true);
        $menu->addChild($marketingTab);

        $eventData = new ConfigureMenuEvent($factory, $menu);
        $this->listener->onNavigationConfigure($eventData);
        $this->assertFalse($dmTab->isDisplayed());
    }
}
