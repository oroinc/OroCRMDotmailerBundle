<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DataFieldTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private DataField $entity;

    protected function setUp(): void
    {
        $this->entity = new DataField();
    }

    public function testProperties()
    {
        $now = new \DateTime();
        $channel = new Channel();
        $organization = $this->createMock(Organization::class);
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
