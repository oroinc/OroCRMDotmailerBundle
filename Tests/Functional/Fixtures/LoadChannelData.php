<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AddressBookConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;

class LoadChannelData extends BaseAbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'first channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactsConnector::TYPE,
                ContactConnector::TYPE,
                ActivityContactConnector::TYPE
            ],
            'transport' => 'orocrm_dotmailer.transport.first',
            'reference' => 'orocrm_dotmailer.channel.first'
        ],
        [
            'name' => 'second channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactsConnector::TYPE,
                ContactConnector::TYPE,
                ActivityContactConnector::TYPE
            ],
            'transport' => 'orocrm_dotmailer.transport.second',
            'reference' => 'orocrm_dotmailer.channel.second'
        ],
        [
            'name' => 'third channel',
            'connectors' => [
                CampaignConnector::TYPE,
                AddressBookConnector::TYPE,
                UnsubscribedContactsConnector::TYPE,
                ContactConnector::TYPE,
                ActivityContactConnector::TYPE
            ],
            'transport' => 'orocrm_dotmailer.transport.third',
            'reference' => 'orocrm_dotmailer.channel.third'
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
            $channel->setType(ChannelType::TYPE);
            $channel->setName($item['name']);
            $channel->setConnectors($item['connectors']);
            $channel->setEnabled(true);
            $channel->setTransport($this->getReference($item['transport']));

            $manager->persist($channel);

            $this->setReference($item['reference'], $channel);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadTransportData'
        ];
    }
}