<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignOpenConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Loods "campaign_click" and "campaign_open" connectors to Dotmailer channel.
 */
class AddClicksAndOpensConnectors extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Channel[] $channels */
        $channels = $manager->getRepository(Channel::class)->findBy(['type' => ChannelType::TYPE]);
        $newConnectors = [CampaignClickConnector::TYPE, CampaignOpenConnector::TYPE];
        foreach ($channels as $channel) {
            $connectors = $channel->getConnectors();
            foreach ($newConnectors as $newConnector) {
                $key = array_search($newConnector, $connectors, true);
                if ($key === false) {
                    $connectors[] = $newConnector;
                }
            }

            $channel->setConnectors($connectors);
        }

        $manager->flush($channels);
    }
}
