<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;

use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Entity\CampaignSummary;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;

class CampaignSummaryTest extends \PHPUnit_Framework_TestCase
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
        $this->entity = new CampaignSummary();
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

        return array(
            'channel' => array('channel', $channel, $channel),
            'campaign' => array('campaign', $campaign, $campaign),
            'dateSent' => array('dateSent', $now, $now),
            'numUniqueOpens' => array('numUniqueOpens', 5, 5),
            'numUniqueTextOpens' => array('numUniqueTextOpens', 5, 5),
            'numTotalUniqueOpens' => array('numTotalUniqueOpens', 5, 5),
            'numOpens' => array('numOpens', 5, 5),
            'numTextOpens' => array('numTextOpens', 5, 5),
            'numTotalOpens' => array('numTotalOpens', 5, 5),
            'numClicks' => array('numClicks', 5, 5),
            'numTextClicks' => array('numTextClicks', 5, 5),
            'numTotalClicks' => array('numTotalClicks', 5, 5),
            'numPageViews' => array('numPageViews', 5, 5),
            'numTotalPageViews' => array('numTotalPageViews', 5, 5),
            'numTextPageViews' => array('numTextPageViews', 5, 5),
            'numForwards' => array('numForwards', 5, 5),
            'numTextForwards' => array('numTextForwards', 5, 5),
            'numEstimatedForwards' => array('numEstimatedForwards', 5, 5),
            'numTextEstimatedForwards' => array('numTextEstimatedForwards', 5, 5),
            'numTotalEstimatedForwards' => array('numTotalEstimatedForwards', 5, 5),
            'numReplies' => array('numReplies', 5, 5),
            'numTextReplies' => array('numTextReplies', 5, 5),
            'numTotalReplies' => array('numTotalReplies', 5, 5),
            'numHardBounces' => array('numHardBounces', 5, 5),
            'numTextHardBounces' => array('numTextHardBounces', 5, 5),
            'numTotalHardBounces' => array('numTotalHardBounces', 5, 5),
            'numSoftBounces' => array('numSoftBounces', 5, 5),
            'numTextSoftBounces' => array('numTextSoftBounces', 5, 5),
            'numTotalSoftBounces' => array('numTotalSoftBounces', 5, 5),
            'numUnsubscribes' => array('numUnsubscribes', 5, 5),
            'numTextUnsubscribes' => array('numTextUnsubscribes', 5, 5),
            'numTotalUnsubscribes' => array('numTotalUnsubscribes', 5, 5),
            'numIspComplaints' => array('numIspComplaints', 5, 5),
            'numTextIspComplaints' => array('numTextIspComplaints', 5, 5),
            'numTotalIspComplaints' => array('numTotalIspComplaints', 5, 5),
            'numMailBlocks' => array('numMailBlocks', 5, 5),
            'numTextMailBlocks' => array('numTextMailBlocks', 5, 5),
            'numTotalMailBlocks' => array('numTotalMailBlocks', 5, 5),
            'numSent' => array('numSent', 5, 5),
            'numTextSent' => array('numTextSent', 5, 5),
            'numTotalSent' => array('numTotalSent', 5, 5),
            'numRecipientsClicked' => array('numRecipientsClicked', 5, 5),
            'numDelivered' => array('numDelivered', 5, 5),
            'numTextDelivered' => array('numTextDelivered', 5, 5),
            'numTotalDelivered' => array('numTotalDelivered', 5, 5),
            'percentageDelivered' => array('percentageDelivered', 5.5, 5.5),
            'percentageUniqueOpens' => array('percentageUniqueOpens', 5.5, 5.5),
            'percentageOpens' => array('percentageOpens', 5.5, 5.5),
            'percentageUnsubscribes' => array('percentageUnsubscribes', 5.5, 5.5),
            'percentageReplies' => array('percentageReplies', 5.5, 5.5),
            'percentageHardBounces' => array('percentageHardBounces', 5.5, 5.5),
            'percentageSoftBounces' => array('percentageSoftBounces', 5.5, 5.5),
            'percentageUsersClicked' => array('percentageUsersClicked', 5.5, 5.5),
            'percentageClicksToOpens' => array('percentageClicksToOpens', 5.5, 5.5),
            'createdAt' => array('createdAt', $now, $now),
            'updatedAt' => array('updatedAt', $now, $now),
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
