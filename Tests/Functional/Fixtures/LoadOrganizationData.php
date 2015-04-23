<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizationData extends AbstractFixture
{
    /** @var array */
    protected $data = [
        [
            'name'      => 'Foo Inc.',
            'enabled'   => true,
            'reference' => 'orocrm_dotmailer.organization.foo',
        ],
        [
            'name'      => 'Bar Inc.',
            'enabled'   => true,
            'reference' => 'orocrm_dotmailer.organization.bar',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $entity = new Organization();
            $entity->setName($data['name']);
            $entity->setEnabled($data['enabled']);

            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
