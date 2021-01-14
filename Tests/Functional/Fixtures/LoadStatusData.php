<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignClickConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignOpenConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class LoadStatusData extends BaseAbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'channel' => 'oro_dotmailer.channel.first',
            'code' => Status::STATUS_FAILED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-10-10'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_FAILED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'oro_dotmailer.channel.third',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'oro_dotmailer.channel.third',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UnsubscribedContactConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => ActivityContactConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignClickConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignOpenConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'oro_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => ContactConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'oro_dotmailer.channel.fourth',
            'code' => Status::STATUS_COMPLETED,
            'connector' => ContactConnector::TYPE,
            'date' => '2015-01-01'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $status = new Status();
            $status->setChannel($this->getReference($item['channel']));
            $status->setCode($item['code']);
            $status->setConnector($item['connector']);
            $status->setDate(date_create($item['date'], new \DateTimeZone('UTC')));
            $status->setMessage('');
            $status->setData([AbstractDotmailerConnector::LAST_SYNC_DATE_KEY => $item['date']]);
            $manager->persist($status);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData'
        ];
    }
}
