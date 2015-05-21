<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

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
            'channel'       => 'orocrm_dotmailer.channel.first',
            'marketingList' => null,
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.address_book.first'
        ],
        [
            'originId'      => 12,
            'name'          => 'test2',
            'contactCount'  => 2,
            'visibility'    => 'Private',
            'channel'       => 'orocrm_dotmailer.channel.first',
            'marketingList' => 'orocrm_dotmailer.marketing_list.second',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.address_book.second'
        ],
        [
            'originId'      => 25,
            'name'          => 'test3',
            'contactCount'  => 4,
            'visibility'    => 'Private',
            'channel'       => 'orocrm_dotmailer.channel.third',
            'marketingList' => 'orocrm_dotmailer.marketing_list.third',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.address_book.third'
        ],
        [
            'originId'      => 35,
            'name'          => 'test4',
            'contactCount'  => 4,
            'visibility'    => 'Private',
            'channel'       => 'orocrm_dotmailer.channel.third',
            'marketingList' => 'orocrm_dotmailer.marketing_list.fourth',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.address_book.fourth'
        ],
        [
            'originId'      => 36,
            'name'          => 'test5',
            'contactCount'  => 6,
            'visibility'    => 'Private',
            'channel'       => 'orocrm_dotmailer.channel.fourth',
            'marketingList' => 'orocrm_dotmailer.marketing_list.fifth',
            'owner'         => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.address_book.fifth'
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
            $this->resolveReferenceIfExist($data, 'channel');
            $this->resolveReferenceIfExist($data, 'marketingList');
            $this->resolveReferenceIfExist($data, 'owner');
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadMarketingListData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
        ];
    }
}
