<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Entity;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DataFieldMappingConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    
    /**
     * @var DataFieldMappingConfig
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
