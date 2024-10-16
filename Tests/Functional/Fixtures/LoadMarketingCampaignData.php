<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CampaignBundle\Entity\Campaign;

class LoadMarketingCampaignData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'name'          => 'Test',
            'code'          => 'test',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'reference'     => 'oro_dotmailer.marketing_campaign.first',
        ],
        [
            'name'          => 'Test 2',
            'code'          => 'test2',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'reference'     => 'oro_dotmailer.marketing_campaign.second',
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Campaign();
            $this->resolveReferenceIfExist($data, 'owner');
            $this->setEntityPropertyValues($entity, $data, ['reference']);

            $this->addReference($data['reference'], $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
        ];
    }
}
