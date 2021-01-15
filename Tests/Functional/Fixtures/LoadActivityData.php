<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\Activity;

class LoadActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'email'                => 'first@mail.com',
            'numOpens'             => 3,
            'numPageViews'         => 0,
            'numClicks'            => 0,
            'numForwards'          => 0,
            'numEstimatedForwards' => 2,
            'numReplies'           => 0,
            'dateSent'             => '2015-04-15T13:48:33.013Z',
            'dateFirstOpened'      => '2015-04-16T13:48:33.013Z',
            'dateLastOpened'       => '2015-04-16T13:48:33.013Z',
            'firstOpenIp'          => '61.249.92.173',
            'unsubscribed'         => false,
            'softBounced'          => false,
            'hardBounced'          => false,
            'contact'              => 'oro_dotmailer.contact.first',
            'campaign'             => 'oro_dotmailer.campaign.first',
            'channel'              => 'oro_dotmailer.channel.second',
            'owner'                => 'oro_dotmailer.organization.foo',
            'reference'            => 'oro_dotmailer.activity.first'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $activity = new Activity();

            $item['dateSent'] = new  \DateTime($item['dateSent'], new \DateTimeZone('UTC'));
            $item['dateFirstOpened'] = new  \DateTime($item['dateFirstOpened'], new \DateTimeZone('UTC'));
            $item['dateLastOpened'] = new  \DateTime($item['dateLastOpened'], new \DateTimeZone('UTC'));

            $this->resolveReferenceIfExist($item, 'contact');
            $this->resolveReferenceIfExist($item, 'channel');
            $this->resolveReferenceIfExist($item, 'campaign');
            $this->resolveReferenceIfExist($item, 'owner');
            $this->setEntityPropertyValues($activity, $item, ['reference']);

            $manager->persist($activity);
            $this->addReference($item['reference'], $activity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData',
        ];
    }
}
