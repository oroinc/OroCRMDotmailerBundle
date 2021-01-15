<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var array */
    protected $data = [
        [
            'name'         => 'Foo Main BU',
            'organization' => 'oro_dotmailer.organization.foo',
            'reference'    => 'oro_dotmailer.business_unit.foo',
        ],
        [
            'name'         => 'Bar Main BU',
            'organization' => 'oro_dotmailer.organization.bar',
            'reference'    => 'oro_dotmailer.business_unit.bar',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new BusinessUnit();

            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'organization');
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadOrganizationData'];
    }
}
