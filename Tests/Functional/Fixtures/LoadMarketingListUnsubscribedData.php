<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;

class LoadMarketingListUnsubscribedData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'marketingListItem' => 'oro_dotmailer.orocrm_contact.nick.case',
            'marketingList' => 'oro_dotmailer.marketing_list.fifth'
        ]
    ];

    #[\Override]
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

    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData'
        ];
    }
}
