<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

use Oro\Bundle\DotmailerBundle\Entity\DataField;

class DataFieldTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');
        $channel = new Channel();
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        return array(
            'channel' => array('channel', $channel, $channel),
            'name' => array('name', 'testName', 'testName'),
            'defaultValue' => array('defaultValue', 'testValue', 'testValue'),
            'notes' => array('notes', 'testNotes', 'testNotes'),
            'createdAt' => array('createdAt', $now, $now),
            'owner' => array('owner', $organization, $organization),
        );
    }

    public function testIdWorks()
    {
        $this->assertEmpty($this->entity->getId());
    }

    public function testPrePersist()
    {
        $this->assertEmpty($this->entity->getCreatedAt());

        $this->entity->prePersist();

        $this->assertInstanceOf('DateTime', $this->entity->getCreatedAt());
    }
}
