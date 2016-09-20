<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

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
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.first',
        ],
        [
            'name'          => 'list2',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'dynamic',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.second'
        ],
        [
            'name'          => 'list3',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.third'
        ],
        [
            'name'          => 'list4',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.fourth'
        ],
        [
            'name'          => 'list5',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.case',
            'reference'     => 'oro_dotmailer.marketing_list.fifth',
        ],
        [
            'name'          => 'list6',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.second',
            'reference'     => 'oro_dotmailer.marketing_list.six',
        ],
        [
            'name'          => 'list7',
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.empty',
            'reference'     => 'oro_dotmailer.marketing_list.up_to_date',
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
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadSegmentData',
        ];
    }
}
