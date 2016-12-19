<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;

class LoadDataFieldMappingData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'syncPriority'  => 100,
            'channel'       => 'orocrm_dotmailer.channel.first',
            'reference'     => 'orocrm_dotmailer.datafield_mapping.first',
            'configs'        => [
                [
                    'dataField' => 'orocrm_dotmailer.datafield.first',
                    'entityField' => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField' => 'orocrm_dotmailer.datafield.second',
                    'entityField' => 'lastName',
                    'isTwoWaySync' => true
                ],
            ]
        ],
        [
            'entity'        => 'OroCRM\Bundle\ContactBundle\Entity\Contact',
            'syncPriority'  => 100,
            'channel'       => 'orocrm_dotmailer.channel.fourth',
            'reference'     => 'orocrm_dotmailer.datafield_mapping.second',
            'configs'        => [
                [
                    'dataField' => 'orocrm_dotmailer.datafield.third',
                    'entityField' => 'firstName',
                    'isTwoWaySync' => false
                ],
                [
                    'dataField' => 'orocrm_dotmailer.datafield.fourth',
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
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData',
        ];
    }
}
