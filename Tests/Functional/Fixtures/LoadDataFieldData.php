<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;

class LoadDataFieldData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name'          => 'FIRSTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test first',
            'channel'       => 'orocrm_dotmailer.channel.first',
            'reference'     => 'orocrm_dotmailer.datafield.first'
        ],
        [
            'name'          => 'LASTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test last',
            'channel'       => 'orocrm_dotmailer.channel.first',
            'reference'     => 'orocrm_dotmailer.datafield.second'
        ],
        [
            'name'          => 'FIRSTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test first',
            'channel'       => 'orocrm_dotmailer.channel.fourth',
            'reference'     => 'orocrm_dotmailer.datafield.third'
        ],
        [
            'name'          => 'LASTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test last',
            'channel'       => 'orocrm_dotmailer.channel.fourth',
            'reference'     => 'orocrm_dotmailer.datafield.fourth'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        foreach ($this->data as $data) {
            $entity = new DataField();
            $data['visibility'] = $this->findEnum('dm_df_visibility', $data['visibility']);
            $data['type'] = $this->findEnum('dm_df_type', $data['type']);
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
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadOrganizationData'
        ];
    }
}
