<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadDataFieldData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
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
        ],
        [
            'name'          => 'FIRSTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test first',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'reference'     => 'oro_dotmailer.datafield.third'
        ],
        [
            'name'          => 'LASTNAME',
            'type'          => 'String',
            'visibility'    => 'Private',
            'defaultValue'  => 'test last',
            'channel'       => 'oro_dotmailer.channel.fourth',
            'reference'     => 'oro_dotmailer.datafield.fourth'
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadChannelData::class, LoadOrganizationData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            $entity = new DataField();
            $data['visibility'] = $this->findEnum('dm_df_visibility', $data['visibility']);
            $data['type'] = $this->findEnum('dm_df_type', $data['type']);
            $entity->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
            $this->resolveReferenceIfExist($data, 'channel');
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->addReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
