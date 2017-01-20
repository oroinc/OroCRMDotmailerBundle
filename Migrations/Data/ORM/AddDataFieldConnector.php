<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;

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
