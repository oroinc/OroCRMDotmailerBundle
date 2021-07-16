<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

/**
 * hide dotmailer menu item if there are no dotmailer integrations
 */
class NavigationListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $dotmailerIntegrations = $this->registry
                ->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE]);
        if (!count($dotmailerIntegrations)) {
            /** @var ItemInterface $marketingMenuItem */
            $marketingMenuItem = $event->getMenu()->getChild('marketing_tab');
            if ($marketingMenuItem && $marketingMenuItem->getChild('oro_dotmailer')) {
                $marketingMenuItem->getChild('oro_dotmailer')->setDisplay(false);
            }
        }
    }
}
