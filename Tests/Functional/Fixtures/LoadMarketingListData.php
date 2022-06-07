<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListType;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;

class LoadMarketingListData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name'          => 'list1',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'dynamic',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.first',
        ],
        [
            'name'          => 'list2',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'dynamic',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.second'
        ],
        [
            'name'          => 'list3',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.third'
        ],
        [
            'name'          => 'list4',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.first',
            'reference'     => 'oro_dotmailer.marketing_list.fourth'
        ],
        [
            'name'          => 'list5',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.case',
            'reference'     => 'oro_dotmailer.marketing_list.fifth',
        ],
        [
            'name'          => 'list6',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.second',
            'reference'     => 'oro_dotmailer.marketing_list.six',
        ],
        [
            'name'          => 'list7',
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.empty',
            'reference'     => 'oro_dotmailer.marketing_list.up_to_date',
        ],
        [
            'name'          => 'list8',
            'entity'        => B2bCustomer::class,
            'type'          => 'static',
            'owner'         => 'oro_dotmailer.user.john.doe',
            'organization'  => 'oro_dotmailer.organization.foo',
            'segment'       => 'oro_dotmailer.segment.b2b_customer',
            'reference'     => 'oro_dotmailer.marketing_list.b2b_customer',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $listTypeRepository = $manager->getRepository(MarketingListType::class);

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
