<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadB2bCustomerEmailData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array Channels configuration
     */
    protected $data = [
        [
            'type' => 'dynamic',
            'name' => 'Test ML Segment',
            'description' => 'description',
            'entity' => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'owner' => 'oro_dotmailer.business_unit.foo',
            'organization' => 'oro_dotmailer.organization.foo',
            'definition' => [
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
                'filters' => [
                    [
                        'columnName' => 'lastName',
                        'criterion' =>
                            [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'Case',
                                    'type' => '1',
                                ],
                            ],
                    ],
                ],
            ],
            'reference' => 'oro_dotmailer.segment.first',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Segment 2',
            'description' => 'description',
            'entity' => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'owner' => 'oro_dotmailer.business_unit.foo',
            'organization' => 'oro_dotmailer.organization.foo',
            'definition' => [
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
                'filters' => [
                    [
                        'columnName' => 'firstName',
                        'criterion' =>
                            [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'Jack',
                                    'type' => '1',
                                ],
                            ],
                    ],
                ],
            ],
            'reference' => 'oro_dotmailer.segment.second',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Segment 3',
            'description' => 'description',
            'entity' => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'owner' => 'oro_dotmailer.business_unit.foo',
            'organization' => 'oro_dotmailer.organization.foo',
            'definition' => [
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
                'filters' => [
                    [
                        'columnName' => 'firstName',
                        'criterion' =>
                            [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'Not Exist',
                                    'type' => '1',
                                ],
                            ],
                    ],
                ],
            ],
            'reference' => 'oro_dotmailer.segment.empty',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Segment by Case last name',
            'description' => 'description',
            'entity' => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'owner' => 'oro_dotmailer.business_unit.foo',
            'organization' => 'oro_dotmailer.organization.foo',
            'definition' => [
                'filters' => [
                    [
                        'columnName' => 'lastName',
                        'criterion' =>
                            [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'Case',
                                    'type' => '1',
                                ],
                            ],
                    ]
                ],
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                    [
                        'name' => 'mlLastContactedAt',
                        'label' => 'Last Contacted At',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
            ],
            'reference' => 'oro_dotmailer.segment.case',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML B2b customer Segment',
            'description' => 'description',
            'entity' => B2bCustomer::class,
            'definition' => [
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
                'filters' => [
                    [
                        [
                            'columnName' => 'primaryEmail',
                            'criterion' => [
                                'filter' => 'string',
                                'data' => [
                                    'value' => '',
                                    'type' => 'filter_not_empty_option'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'reference' => 'oro_dotmailer.segment.b2b_customer',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Segment();
            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'organization');
            $data['definition'] = json_encode($data['definition']);
            $data['type'] = $manager->getRepository(SegmentType::class)->find($data['type']);
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
            LoadUserData::class,
            LoadB2bCustomerEmailData::class
        ];
    }
}
