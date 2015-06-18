<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;

class LoadEmailCampaignData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'name'          => 'Test',
            'schedule'      => 'manual',
            'transport'     => 'Test',
            'marketingList' => 'orocrm_dotmailer.marketing_list.second',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'reference'     => 'orocrm_dotmailer.email_campaign.first',
        ],
        [
            'name'          => 'Test 2',
            'schedule'      => 'manual',
            'transport'     => 'Test 2',
            'marketingList' => 'orocrm_dotmailer.marketing_list.third',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'reference'     => 'orocrm_dotmailer.email_campaign.second',
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListData',
        ];
    }
}
