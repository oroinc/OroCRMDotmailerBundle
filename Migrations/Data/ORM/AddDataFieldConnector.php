<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Loads "data_field" connector to Dotmailer channel.
 */
class AddDataFieldConnector extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Channel[] $channels */
        $channels = $manager->getRepository(Channel::class)->findBy(['type' => ChannelType::TYPE]);
        foreach ($channels as $channel) {
            $connectors = $channel->getConnectors();
            $key = array_search(DataFieldConnector::TYPE, $connectors, true);
            if ($key === false) {
                $connectors[] = DataFieldConnector::TYPE;
            }
            $channel->setConnectors($connectors);
        }
        $manager->flush($channels);
    }
}
