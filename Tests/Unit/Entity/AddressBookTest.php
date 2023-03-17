<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class AddressBookTest extends \PHPUnit\Framework\TestCase
{
    private AddressBook $entity;

    protected function setUp(): void
    {
        $this->entity = new AddressBook();
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
        $marketingList = new MarketingList();

        return [
            'channel' => ['channel', $channel, $channel],
            'marketingList' => ['marketingList', $marketingList, $marketingList],
            'name' => ['name', 'testName', 'testName'],
            'contactCount' => ['contactCount', 10, 10],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
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

    public function testAddCampaign()
    {
        $this->assertEmpty($this->entity->getCampaigns()->toArray());

        $campaign = new Campaign();
        $this->entity->addCampaign($campaign);
        $campaigns = $this->entity->getCampaigns()->toArray();
        $this->assertCount(1, $campaigns);
        $this->assertEquals($campaign, current($campaigns));
    }

    public function testRemoveCampaign()
    {
        $this->assertEmpty($this->entity->getCampaigns()->toArray());

        $campaign = new Campaign();
        $this->entity->addCampaign($campaign);
        $campaigns = $this->entity->getCampaigns()->toArray();
        $this->assertCount(1, $campaigns);
        $this->assertEquals($campaign, current($campaigns));
        $this->entity->removeCampaign($campaign);
        $this->assertEmpty($this->entity->getCampaigns()->toArray());
    }

    public function testHasCampaign()
    {
        $this->assertEmpty($this->entity->getCampaigns()->toArray());

        $campaign = new Campaign();
        $this->assertFalse($this->entity->hasCampaign($campaign));
        $this->entity->addCampaign($campaign);
        $campaigns = $this->entity->getCampaigns()->toArray();
        $this->assertCount(1, $campaigns);
        $this->assertTrue($this->entity->hasCampaign($campaign));
    }

    public function testSetCampaigns()
    {
        $this->assertEmpty($this->entity->getCampaigns()->toArray());

        $campaign = new Campaign();
        $this->entity->addCampaign($campaign);
        $campaigns = $this->entity->getCampaigns()->toArray();
        $this->assertCount(1, $campaigns);
        $this->assertEquals($campaign, current($campaigns));
        $this->entity->setCampaigns(new ArrayCollection());
        $this->assertEmpty($this->entity->getCampaigns()->toArray());
    }
}
