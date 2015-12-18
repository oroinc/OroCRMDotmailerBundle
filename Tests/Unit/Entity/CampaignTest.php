<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CampaignTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Campaign
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new Campaign();
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
        $summary = new CampaignSummary();
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        return array(
            'channel' => array('channel', $channel, $channel),
            'name' => array('name', 'testName', 'testName'),
            'subject' => array('subject', 'testSubject', 'testSubject'),
            'fromName' => array('fromName', 'testFrom', 'testFrom'),
            'fromAddress' => array('fromAddress', 'test@from.com', 'test@from.com'),
            'htmlContent' => array('htmlContent', 'content', 'content'),
            'plainTextContent' => array('plainTextContent', 'content', 'content'),
            'replyToAddress' => array('replyToAddress', 'test@reply.com', 'test@reply.com'),
            'isSplitTest' => array('isSplitTest', false, false),
            'createdAt' => array('createdAt', $now, $now),
            'updatedAt' => array('updatedAt', $now, $now),
            'owner' => array('owner', $organization, $organization),
            'campaignSummary' => array('campaignSummary', $summary, $summary),
        );
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
