<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;

class LoadDataFieldMappingData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'entity'        => 'Oro\Bundle\SalesBundle\Entity\Lead',
            'syncPriority'  => 100,
            'channel'       => 'oro_dotmailer.channel.first',
            'reference'     => 'oro_dotmailer.datafield_mapping.first'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        foreach ($this->data as $data) {
            $entity = new DataFieldMapping();
            $entity->setOwner($organization);
            $this->resolveReferenceIfExist($data, 'channel');
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
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData',
        ];
    }
}
