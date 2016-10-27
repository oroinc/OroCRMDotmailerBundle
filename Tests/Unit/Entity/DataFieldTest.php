<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Component\Testing\Unit\EntityTestCase;

class DataFieldTest extends EntityTestCase
{
    /**
     * @var DataField
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new DataField();
    }

    public function testProperties()
    {
        $now = new \DateTime('now');
        $channel = new Channel();
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $properties = [
            ['id', 1],
            ['channel', $channel],
            ['name', 'testName'],
            ['defaultValue', 'testValue'],
            ['notes', 'testNotes'],
            ['createdAt', $now],
            ['owner', $organization],
            ['forceRemove', 1],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testPrePersist()
    {
        $this->assertEmpty($this->entity->getCreatedAt());

        $this->entity->prePersist();

        $this->assertInstanceOf('DateTime', $this->entity->getCreatedAt());
    }
}
