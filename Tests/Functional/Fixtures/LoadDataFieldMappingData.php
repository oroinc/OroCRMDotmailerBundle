<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadDataFieldMappingData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'syncPriority'  => 100,
            'channel'       => 'oro_dotmailer.channel.first',
            'reference'     => 'oro_dotmailer.datafield_mapping.first',
            'configs'        => [
                [
                    'dataField' => 'oro_dotmailer.datafield.first',
                    'entityField' => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField' => 'oro_dotmailer.datafield.second',
                    'entityField' => 'lastName',
                    'isTwoWaySync' => true
                ],
            ]
        ],
        [
            'entity'        => 'Oro\Bundle\ContactBundle\Entity\Contact',
            'syncPriority'  => 100,
            'channel'       => 'oro_dotmailer.channel.fourth',
            'reference'     => 'oro_dotmailer.datafield_mapping.second',
            'configs'        => [
                [
                    'dataField' => 'oro_dotmailer.datafield.third',
                    'entityField' => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField' => 'oro_dotmailer.datafield.fourth',
                    'entityField' => 'lastName',
                    'isTwoWaySync' => false
                ],
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        foreach ($this->data as $data) {
            $entity = new DataFieldMapping();
            $entity->setOwner($organization);
            $this->resolveReferenceIfExist($data, 'channel');
            $this->setEntityPropertyValues($entity, $data, ['reference', 'configs']);
            if (!empty($data['configs'])) {
                foreach ($data['configs'] as $config) {
                    $mappingConfig = new DataFieldMappingConfig();
                    $mappingConfig->setDataField($this->getReference($config['dataField']));
                    $mappingConfig->setEntityFields($config['entityField']);
                    $mappingConfig->setIsTwoWaySync($config['isTwoWaySync']);
                    $entity->addConfig($mappingConfig);
                }
            }
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
