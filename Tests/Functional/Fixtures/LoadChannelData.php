<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignOpenConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ExportContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'first channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactConnector::TYPE,
                ActivityContactConnector::TYPE,
                CampaignSummaryConnector::TYPE,
                ContactConnector::TYPE,
                ExportContactConnector::TYPE,
                DataFieldConnector::TYPE,
            ],
            'enabled' => true,
            'transport' => 'oro_dotmailer.transport.first',
            'reference' => 'oro_dotmailer.channel.first'
        ],
        [
            'name' => 'second channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactConnector::TYPE,
                ActivityContactConnector::TYPE,
                CampaignSummaryConnector::TYPE,
                ContactConnector::TYPE,
                ExportContactConnector::TYPE,
                CampaignClickConnector::TYPE,
                CampaignOpenConnector::TYPE
            ],
            'enabled' => true,
            'transport' => 'oro_dotmailer.transport.second',
            'reference' => 'oro_dotmailer.channel.second'
        ],
        [
            'name' => 'third channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactConnector::TYPE,
                ActivityContactConnector::TYPE,
                CampaignSummaryConnector::TYPE,
                ContactConnector::TYPE,
                ExportContactConnector::TYPE
            ],
            'enabled' => true,
            'transport' => 'oro_dotmailer.transport.third',
            'reference' => 'oro_dotmailer.channel.third'
        ],
        [
            'name' => 'fourth channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactConnector::TYPE,
                ActivityContactConnector::TYPE,
                CampaignSummaryConnector::TYPE,
                ContactConnector::TYPE,
                ExportContactConnector::TYPE
            ],
            'enabled' => true,
            'transport' => 'oro_dotmailer.transport.fourth',
            'reference' => 'oro_dotmailer.channel.fourth'
        ],
        [
            'name' => 'disabled channel 1',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactConnector::TYPE,
                ActivityContactConnector::TYPE,
                CampaignSummaryConnector::TYPE,
                ContactConnector::TYPE,
                ExportContactConnector::TYPE
            ],
            'enabled' => false,
            'transport' => 'oro_dotmailer.transport.fifth',
            'reference' => 'oro_dotmailer.channel.disabled.first'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->data as $item) {
            $channel = new Channel();
            $channel->setOrganization($admin->getOrganization());
            $channel->setDefaultUserOwner($admin);
            $channel->setType(ChannelType::TYPE);
            $channel->setName($item['name']);
            $channel->setConnectors($item['connectors']);
            $channel->setEnabled($item['enabled'] ?? true);
            $channel->setTransport($this->getReference($item['transport']));

            $manager->persist($channel);

            $this->setReference($item['reference'], $channel);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadTransportData::class,
        ];
    }
}
