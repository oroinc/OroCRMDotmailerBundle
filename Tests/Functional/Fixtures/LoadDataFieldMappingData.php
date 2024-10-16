<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadDataFieldMappingData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'entity'       => Contact::class,
            'syncPriority' => 100,
            'channel'      => 'oro_dotmailer.channel.first',
            'reference'    => 'oro_dotmailer.datafield_mapping.first',
            'configs'      => [
                [
                    'dataField'    => 'oro_dotmailer.datafield.first',
                    'entityField'  => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField'    => 'oro_dotmailer.datafield.second',
                    'entityField'  => 'lastName',
                    'isTwoWaySync' => true
                ],
            ]
        ],
        [
            'entity'       => Contact::class,
            'syncPriority' => 100,
            'channel'      => 'oro_dotmailer.channel.fourth',
            'reference'    => 'oro_dotmailer.datafield_mapping.second',
            'configs'      => [
                [
                    'dataField'    => 'oro_dotmailer.datafield.third',
                    'entityField'  => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField'    => 'oro_dotmailer.datafield.fourth',
                    'entityField'  => 'lastName',
                    'isTwoWaySync' => false
                ],
            ]
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadDataFieldData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            $entity = new DataFieldMapping();
            $entity->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
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
}
