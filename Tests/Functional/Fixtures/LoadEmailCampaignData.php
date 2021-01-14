<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;

class LoadEmailCampaignData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'name'          => 'Test',
            'schedule'      => 'manual',
            'transport'     => 'Test',
            'marketingList' => 'oro_dotmailer.marketing_list.second',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'campaign'      => 'oro_dotmailer.marketing_campaign.first',
            'reference'     => 'oro_dotmailer.email_campaign.first',
        ],
        [
            'name'          => 'Test 2',
            'schedule'      => 'manual',
            'transport'     => 'Test 2',
            'marketingList' => 'oro_dotmailer.marketing_list.third',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'campaign'      => 'oro_dotmailer.marketing_campaign.second',
            'reference'     => 'oro_dotmailer.email_campaign.second',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new EmailCampaign();
            $this->resolveReferenceIfExist($data, 'marketingList');
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
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingCampaignData',
        ];
    }
}
