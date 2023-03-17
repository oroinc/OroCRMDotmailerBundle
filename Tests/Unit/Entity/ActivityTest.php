<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    private Activity $entity;

    protected function setUp(): void
    {
        $this->entity = new Activity();
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
        $organization = new Organization();
        $campaign = new Campaign();
        $contact = new Contact();

        return [
            'channel' => ['channel', $channel, $channel],
            'campaign' => ['campaign', $campaign, $campaign],
            'contact' => ['contact', $contact, $contact],
            'email' => ['email', 'test@from.com', 'test@from.com'],
            'numOpens' => ['numOpens', 5, 5],
            'numPageViews' => ['numPageViews', 3, 3],
            'numClicks' => ['numClicks', 15, 15],
            'numForwards' => ['numForwards', 2, 2],
            'numEstimatedForwards' => ['numEstimatedForwards', 5, 5],
            'numReplies' => ['numReplies', 5, 5],
            'dateSent' => ['dateSent', $now, $now],
            'dateFirstOpened' => ['dateFirstOpened', $now, $now],
            'dateLastOpened' => ['dateLastOpened', $now, $now],
            'firstOpenIp' => ['firstOpenIp', '127.0.0.1', '127.0.0.1'],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
            'owner' => ['owner', $organization, $organization],
        ];
    }

    public function testIdWorks()
    {
        $this->assertEmpty($this->entity->getId());
    }

    public function testUnsubscribedWorks()
    {
        $this->assertNull($this->entity->isUnsubscribed());
        $this->entity->setUnsubscribed(true);
        $this->assertTrue($this->entity->isUnsubscribed());
        $this->entity->setUnsubscribed(false);
        $this->assertFalse($this->entity->isUnsubscribed());
        $this->entity->setUnsubscribed(null);
        $this->assertNull($this->entity->isUnsubscribed());
    }

    public function testSoftBouncedWorks()
    {
        $this->assertNull($this->entity->isSoftBounced());
        $this->entity->setSoftBounced(true);
        $this->assertTrue($this->entity->isSoftBounced());
        $this->entity->setSoftBounced(false);
        $this->assertFalse($this->entity->isSoftBounced());
        $this->entity->setSoftBounced(null);
        $this->assertNull($this->entity->isSoftBounced());
    }

    public function testHardBouncedWorks()
    {
        $this->assertNull($this->entity->isHardBounced());
        $this->entity->setHardBounced(true);
        $this->assertTrue($this->entity->isHardBounced());
        $this->entity->setHardBounced(false);
        $this->assertFalse($this->entity->isHardBounced());
        $this->entity->setHardBounced(null);
        $this->assertNull($this->entity->isHardBounced());
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
}
