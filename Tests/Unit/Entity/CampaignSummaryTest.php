<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\CampaignSummary;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;

class CampaignSummaryTest extends \PHPUnit\Framework\TestCase
{
    private CampaignSummary $entity;

    protected function setUp(): void
    {
        $this->entity = new CampaignSummary();
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

        return [
            'channel' => ['channel', $channel, $channel],
            'campaign' => ['campaign', $campaign, $campaign],
            'dateSent' => ['dateSent', $now, $now],
            'numUniqueOpens' => ['numUniqueOpens', 5, 5],
            'numUniqueTextOpens' => ['numUniqueTextOpens', 5, 5],
            'numTotalUniqueOpens' => ['numTotalUniqueOpens', 5, 5],
            'numOpens' => ['numOpens', 5, 5],
            'numTextOpens' => ['numTextOpens', 5, 5],
            'numTotalOpens' => ['numTotalOpens', 5, 5],
            'numClicks' => ['numClicks', 5, 5],
            'numTextClicks' => ['numTextClicks', 5, 5],
            'numTotalClicks' => ['numTotalClicks', 5, 5],
            'numPageViews' => ['numPageViews', 5, 5],
            'numTotalPageViews' => ['numTotalPageViews', 5, 5],
            'numTextPageViews' => ['numTextPageViews', 5, 5],
            'numForwards' => ['numForwards', 5, 5],
            'numTextForwards' => ['numTextForwards', 5, 5],
            'numEstimatedForwards' => ['numEstimatedForwards', 5, 5],
            'numTextEstimatedForwards' => ['numTextEstimatedForwards', 5, 5],
            'numTotalEstimatedForwards' => ['numTotalEstimatedForwards', 5, 5],
            'numReplies' => ['numReplies', 5, 5],
            'numTextReplies' => ['numTextReplies', 5, 5],
            'numTotalReplies' => ['numTotalReplies', 5, 5],
            'numHardBounces' => ['numHardBounces', 5, 5],
            'numTextHardBounces' => ['numTextHardBounces', 5, 5],
            'numTotalHardBounces' => ['numTotalHardBounces', 5, 5],
            'numSoftBounces' => ['numSoftBounces', 5, 5],
            'numTextSoftBounces' => ['numTextSoftBounces', 5, 5],
            'numTotalSoftBounces' => ['numTotalSoftBounces', 5, 5],
            'numUnsubscribes' => ['numUnsubscribes', 5, 5],
            'numTextUnsubscribes' => ['numTextUnsubscribes', 5, 5],
            'numTotalUnsubscribes' => ['numTotalUnsubscribes', 5, 5],
            'numIspComplaints' => ['numIspComplaints', 5, 5],
            'numTextIspComplaints' => ['numTextIspComplaints', 5, 5],
            'numTotalIspComplaints' => ['numTotalIspComplaints', 5, 5],
            'numMailBlocks' => ['numMailBlocks', 5, 5],
            'numTextMailBlocks' => ['numTextMailBlocks', 5, 5],
            'numTotalMailBlocks' => ['numTotalMailBlocks', 5, 5],
            'numSent' => ['numSent', 5, 5],
            'numTextSent' => ['numTextSent', 5, 5],
            'numTotalSent' => ['numTotalSent', 5, 5],
            'numRecipientsClicked' => ['numRecipientsClicked', 5, 5],
            'numDelivered' => ['numDelivered', 5, 5],
            'numTextDelivered' => ['numTextDelivered', 5, 5],
            'numTotalDelivered' => ['numTotalDelivered', 5, 5],
            'percentageDelivered' => ['percentageDelivered', 5.5, 5.5],
            'percentageUniqueOpens' => ['percentageUniqueOpens', 5.5, 5.5],
            'percentageOpens' => ['percentageOpens', 5.5, 5.5],
            'percentageUnsubscribes' => ['percentageUnsubscribes', 5.5, 5.5],
            'percentageReplies' => ['percentageReplies', 5.5, 5.5],
            'percentageHardBounces' => ['percentageHardBounces', 5.5, 5.5],
            'percentageSoftBounces' => ['percentageSoftBounces', 5.5, 5.5],
            'percentageUsersClicked' => ['percentageUsersClicked', 5.5, 5.5],
            'percentageClicksToOpens' => ['percentageClicksToOpens', 5.5, 5.5],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
            'owner' => ['owner', $organization, $organization],
        ];
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
}
