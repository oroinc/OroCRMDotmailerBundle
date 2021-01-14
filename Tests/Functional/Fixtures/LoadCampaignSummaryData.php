<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\CampaignSummary;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCampaignSummaryData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            'numUniqueOpens' => 15,
            'numUniqueTextOpens' => 5,
            'numTotalUniqueOpens' => 5,
            'numOpens' => 15,
            'numTextOpens' => 5,
            'numTotalOpens' => 5,
            'numClicks' => 5,
            'numTextClicks' => 5,
            'numTotalClicks' => 5,
            'owner'         => 'oro_dotmailer.organization.foo',
            'channel'       => 'oro_dotmailer.channel.second',
            'campaign'     => 'oro_dotmailer.campaign.first',
            'reference'     => 'oro_dotmailer.campaign_summary.first',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new CampaignSummary();
            $this->resolveReferenceIfExist($data, 'channel');
            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'campaign');
            $this->setEntityPropertyValues($entity, $data, ['reference']);

            $this->addReference($data['reference'], $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData',
        ];
    }
}
