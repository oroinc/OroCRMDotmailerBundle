<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;

use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class ActivityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Activity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new Activity();
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
        $organization = new Organization();
        $campaign = new Campaign();
        $contact = new Contact();

        return array(
            'channel' => array('channel', $channel, $channel),
            'campaign' => array('campaign', $campaign, $campaign),
            'contact' => array('contact', $contact, $contact),
            'email' => array('email', 'test@from.com', 'test@from.com'),
            'numOpens' => array('numOpens', 5, 5),
            'numPageViews' => array('numPageViews', 3, 3),
            'numClicks' => array('numClicks', 15, 15),
            'numForwards' => array('numForwards', 2, 2),
            'numEstimatedForwards' => array('numEstimatedForwards', 5, 5),
            'numReplies' => array('numReplies', 5, 5),
            'dateSent' => array('dateSent', $now, $now),
            'dateFirstOpened' => array('dateFirstOpened', $now, $now),
            'dateLastOpened' => array('dateLastOpened', $now, $now),
            'firstOpenIp' => array('firstOpenIp', '127.0.0.1', '127.0.0.1'),
            'createdAt' => array('createdAt', $now, $now),
            'updatedAt' => array('updatedAt', $now, $now),
            'owner' => array('owner', $organization, $organization),
        );
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
