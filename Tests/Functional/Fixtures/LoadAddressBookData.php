<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

class LoadAddressBookData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'originId'      => 11,
            'name'          => 'test1',
            'contactCount'  => 23,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.first',
            'marketingList' => null,
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.first'
        ],
        [
            'originId'       => 12,
            'name'           => 'test2',
            'contactCount'   => 2,
            'visibility'     => 'Private',
            'channel'        => 'oro_dotmailer.channel.first',
            'marketingList'  => 'oro_dotmailer.marketing_list.second',
            'campaign'       => 'oro_dotmailer.campaign.first',
            'createEntities' => true,
            'owner'          => 'oro_dotmailer.organization.foo',
            'reference'      => 'oro_dotmailer.address_book.second'
        ],
        [
            'originId'      => 25,
            'name'          => 'test3',
            'contactCount'  => 4,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.third',
            'marketingList' => 'oro_dotmailer.marketing_list.third',
            'campaign'      => 'oro_dotmailer.campaign.fifth',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.third'
        ],
        [
            'originId'      => 35,
            'name'          => 'test4',
            'contactCount'  => 4,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.third',
            'marketingList' => 'oro_dotmailer.marketing_list.fourth',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.fourth'
        ],
        [
            'originId'      => 36,
            'name'          => 'test5',
            'contactCount'  => 6,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'marketingList' => 'oro_dotmailer.marketing_list.fifth',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.fifth'
        ],
        [
            'originId'      => 37,
            'name'          => 'test6',
            'contactCount'  => 6,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'marketingList' => 'oro_dotmailer.marketing_list.six',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.six'
        ],
        [
            'originId'      => 38,
            'name'          => 'test7',
            'contactCount'  => 0,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'marketingList' => 'oro_dotmailer.marketing_list.up_to_date',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.up_to_date'
        ],
        [
            'originId'      => 39,
            'name'          => 'test8',
            'contactCount'  => 0,
            'visibility'    => 'Private',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'marketingList' => 'oro_dotmailer.marketing_list.b2b_customer',
            'owner'         => 'oro_dotmailer.organization.foo',
            'reference'     => 'oro_dotmailer.address_book.b2b_customer'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new AddressBook();
            $data['visibility'] = $this->findEnum('dm_ab_visibility', $data['visibility']);
            $this->resolveReferenceIfExist($data, 'campaign');
            $this->resolveReferenceIfExist($data, 'channel');
            $this->resolveReferenceIfExist($data, 'marketingList');
            $this->resolveReferenceIfExist($data, 'owner');
            if (isset($data['campaign'])) {
                $entity->addCampaign($data['campaign']);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference', 'campaign']);

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
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
        ];
    }
}
