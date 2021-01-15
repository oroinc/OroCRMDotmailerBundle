<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class AddDataFieldConnector extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Channel[] $channels */
        $channels = $manager->getRepository('OroIntegrationBundle:Channel')->findBy(['type' => ChannelType::TYPE]);

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
