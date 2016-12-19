<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Knp\Menu\ItemInterface;

use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
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

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $dotmailerIntegrations = $this->registry
                ->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE]);
        if (!count($dotmailerIntegrations)) {
            /** @var ItemInterface $marketingMenuItem */
            $marketingMenuItem = $event->getMenu()->getChild('marketing_tab');
            if ($marketingMenuItem && $marketingMenuItem->getChild('orocrm_dotmailer')) {
                $marketingMenuItem->getChild('orocrm_dotmailer')->setDisplay(false);
            }
        }
    }
}
