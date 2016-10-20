<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DotmailerBundle\Entity\DataField;

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
            'channel'       => 'oro_dotmailer.channel.first',
            'reference'     => 'oro_dotmailer.datafield.first'
        ],
        [
            'name'          => 'LASTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test last',
            'channel'       => 'oro_dotmailer.channel.first',
            'reference'     => 'oro_dotmailer.datafield.second'
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
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadOrganizationData'
        ];
    }
}
