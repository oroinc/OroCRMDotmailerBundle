<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

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
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.first',
            'reference'     => 'orocrm_dotmailer.marketing_list.first',
        ],
        [
            'name'          => 'list2',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'dynamic',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.first',
            'reference'     => 'orocrm_dotmailer.marketing_list.second'
        ],
        [
            'name'          => 'list3',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.first',
            'reference'     => 'orocrm_dotmailer.marketing_list.third'
        ],
        [
            'name'          => 'list4',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.first',
            'reference'     => 'orocrm_dotmailer.marketing_list.fourth'
        ],
        [
            'name'          => 'list5',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.case',
            'reference'     => 'orocrm_dotmailer.marketing_list.fifth',
        ],
        [
            'name'          => 'list6',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.second',
            'reference'     => 'orocrm_dotmailer.marketing_list.six',
        ],
        [
            'name'          => 'list7',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'orocrm_dotmailer.user.john.doe',
            'organization'  => 'orocrm_dotmailer.organization.foo',
            'segment'       => 'orocrm_dotmailer.segment.empty',
            'reference'     => 'orocrm_dotmailer.marketing_list.up_to_date',
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
