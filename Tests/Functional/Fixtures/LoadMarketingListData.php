<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

class LoadMarketingListData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name'          => 'list1',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.first',
        ],
        [
            'name'          => 'list2',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.second'
        ],
        [
            'name'          => 'list3',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.third'
        ],
        [
            'name'          => 'list4',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.fourth'
        ],
        [
            'name'          => 'list5',
            'entity'        => 'CB\Bundle\WebsphereBundle\Entity\Subscriber',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'reference'     => 'orocrm_dotmailer.marketing_list.fifth'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $listTypeRepository = $manager->getRepository('OroCRMMarketingListBundle:MarketingListType');

        foreach ($this->data as $data) {
            $entity = new MarketingList();
            $data['type']       = $listTypeRepository->find($data['type']);
            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'organization');
            $this->resolveReferenceIfExist($data, 'segment');
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadSegmentData',
        ];
    }
}
