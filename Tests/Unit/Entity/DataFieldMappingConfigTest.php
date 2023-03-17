<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DataFieldMappingConfigTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private DataFieldMappingConfig $entity;

    protected function setUp(): void
    {
        $this->entity = new DataFieldMappingConfig();
    }

    public function testProperties()
    {
        $now = new \DateTime();
        $dataField = new DataField();
        $mapping = new DataFieldMapping();

        $properties = [
            ['id', 1],
            ['entityFields', 'Entity Field'],
            ['dataField', $dataField],
            ['isTwoWaySync', true],
            ['mapping', $mapping],
            ['createdAt', $now],
            ['updatedAt', $now]
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testPrePersist()
    {
        $this->entity->prePersist();
        $this->assertInstanceOf('DateTime', $this->entity->getCreatedAt());
        $this->assertInstanceOf('DateTime', $this->entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertEmpty($this->entity->getUpdatedAt());
        $this->entity->preUpdate();
        $this->assertInstanceOf('DateTime', $this->entity->getUpdatedAt());
    }
}
