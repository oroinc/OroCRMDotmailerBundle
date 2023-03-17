<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\CampaignSummary;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CampaignTest extends \PHPUnit\Framework\TestCase
{
    private Campaign $entity;

    protected function setUp(): void
    {
        $this->entity = new Campaign();
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
        $summary = new CampaignSummary();
        $organization = $this->createMock(Organization::class);

        return [
            'channel' => ['channel', $channel, $channel],
            'name' => ['name', 'testName', 'testName'],
            'subject' => ['subject', 'testSubject', 'testSubject'],
            'fromName' => ['fromName', 'testFrom', 'testFrom'],
            'fromAddress' => ['fromAddress', 'test@from.com', 'test@from.com'],
            'htmlContent' => ['htmlContent', 'content', 'content'],
            'plainTextContent' => ['plainTextContent', 'content', 'content'],
            'replyToAddress' => ['replyToAddress', 'test@reply.com', 'test@reply.com'],
            'isSplitTest' => ['isSplitTest', false, false],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
            'owner' => ['owner', $organization, $organization],
            'campaignSummary' => ['campaignSummary', $summary, $summary],
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

    public function testAddAddressBook()
    {
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
        $addressBook = new AddressBook();
        $this->entity->addAddressBook($addressBook);
        $addressBooks = $this->entity->getAddressBooks()->toArray();
        $this->assertCount(1, $addressBooks);
        $this->assertEquals($addressBook, current($addressBooks));
    }

    public function testRemoveAddressBook()
    {
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
        $addressBook = new AddressBook();
        $this->entity->addAddressBook($addressBook);
        $addressBooks = $this->entity->getAddressBooks()->toArray();
        $this->assertCount(1, $addressBooks);
        $this->assertEquals($addressBook, current($addressBooks));
        $this->entity->removeAddressBook($addressBook);
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
    }

    public function testHasAddressBooks()
    {
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
        $addressBook = new AddressBook();
        $this->assertFalse($this->entity->hasAddressBooks());
        $this->entity->addAddressBook($addressBook);
        $addressBooks = $this->entity->getAddressBooks()->toArray();
        $this->assertCount(1, $addressBooks);
        $this->assertTrue($this->entity->hasAddressBooks());
    }

    public function testSetAddressBooks()
    {
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
        $addressBook = new AddressBook();
        $this->entity->addAddressBook($addressBook);
        $addressBooks = $this->entity->getAddressBooks()->toArray();
        $this->assertCount(1, $addressBooks);
        $this->assertEquals($addressBook, current($addressBooks));
        $this->entity->setAddressBooks(new ArrayCollection());
        $this->assertEmpty($this->entity->getAddressBooks()->toArray());
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
