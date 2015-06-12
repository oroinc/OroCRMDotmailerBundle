<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;

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
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'channel'       => 'orocrm_dotmailer.channel.second',
            'campaign'     => 'orocrm_dotmailer.campaign.first',
            'reference'     => 'orocrm_dotmailer.campaign_summary.first',
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadCampaignData',
        ];
    }
}
