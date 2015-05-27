<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;

class LoadMarketingListUnsubscribedData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'marketingListItem' => 'orocrm_dotmailer.orocrm_contact.nick.case',
            'marketingList' => 'orocrm_dotmailer.marketing_list.fifth'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $entity = new MarketingListUnsubscribedItem();

            $marketingListItem = $this->getReference($item['marketingListItem']);

            $entity->setEntityId($marketingListItem->getId());
            $entity->setMarketingList($this->getReference($item['marketingList']));

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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
        ];
    }
}
