<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactsConnector;

class LoadStatusData extends BaseAbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'channel' => 'orocrm_dotmailer.channel.first',
            'code' => Status::STATUS_FAILED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'orocrm_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-01-01'
        ],
        [
            'channel' => 'orocrm_dotmailer.channel.second',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-10-10'
        ],
        [
            'channel' => 'orocrm_dotmailer.channel.second',
            'code' => Status::STATUS_FAILED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'orocrm_dotmailer.channel.third',
            'code' => Status::STATUS_COMPLETED,
            'connector' => CampaignConnector::TYPE,
            'date' => '2015-11-11'
        ],
        [
            'channel' => 'orocrm_dotmailer.channel.third',
            'code' => Status::STATUS_COMPLETED,
            'connector' => UnsubscribedContactsConnector::TYPE,
            'date' => '2015-11-11'
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
    function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData'
        ];
    }
}
