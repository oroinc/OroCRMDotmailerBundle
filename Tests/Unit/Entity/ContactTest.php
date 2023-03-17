<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ContactTest extends \PHPUnit\Framework\TestCase
{
    private Contact $entity;

    protected function setUp(): void
    {
        $this->entity = new Contact();
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet(string $property, mixed $value, mixed $expected)
    {
        call_user_func([$this->entity, 'set' . ucfirst($property)], $value);
        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider(): array
    {
        $now = new \DateTime('now');
        $channel = new Channel();
        $organization = $this->createMock(Organization::class);
        $dataFields = ['test_field' => 'test'];

        return [
            'channel' => ['channel', $channel, $channel],
            'email' => ['email', 'TEST@from.com', 'test@from.com'],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
            'dataFields' => ['dataFields', $dataFields, $dataFields],
            'owner' => ['owner', $organization, $organization],
        ];
    }

    public function testOriginIdWorks()
    {
        $this->entity->setOriginId(1);
        $this->assertEquals(1, $this->entity->getOriginId());
    }

    public function testIdWorks()
    {
        $this->assertEmpty($this->entity->getId());
    }

    public function testPrePersist()
    {
        $this->assertEmpty($this->entity->getCreatedAt());
        $this->assertEmpty($this->entity->getUpdatedAt());

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

    public function testAddActivity()
    {
        $this->assertEmpty($this->entity->getActivities()->toArray());

        $activity = new Activity();
        $this->entity->addActivity($activity);
        $activities = $this->entity->getActivities()->toArray();
        $this->assertCount(1, $activities);
        $this->assertEquals($activity, current($activities));
    }

    public function testRemoveActivity()
    {
        $this->assertEmpty($this->entity->getActivities()->toArray());

        $activity = new Activity();
        $this->entity->addActivity($activity);
        $activities = $this->entity->getActivities()->toArray();
        $this->assertCount(1, $activities);
        $this->assertEquals($activity, current($activities));
        $this->entity->removeActivity($activity);
        $this->assertEmpty($this->entity->getActivities()->toArray());
    }

    public function testSetActivity()
    {
        $this->assertEmpty($this->entity->getActivities()->toArray());

        $activity = new Activity();
        $this->entity->addActivity($activity);
        $activities = $this->entity->getActivities()->toArray();
        $this->assertCount(1, $activities);
        $this->assertEquals($activity, current($activities));
        $this->entity->setActivities(new ArrayCollection());
        $this->assertEmpty($this->entity->getActivities()->toArray());
    }

    public function testHasActivities()
    {
        $this->assertEmpty($this->entity->getActivities()->toArray());
        $activity = new Activity();
        $this->assertFalse($this->entity->hasActivities());
        $this->entity->addActivity($activity);
        $this->assertTrue($this->entity->hasActivities());
    }
}
