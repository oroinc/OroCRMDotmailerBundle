<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\DataTypes;

use DotMailer\Api\DataTypes\ApiCampaignSummary;
use PHPUnit\Framework\TestCase;

class ApiCampaignSummaryTest extends TestCase
{
    public function testCreate()
    {
        $values = [
            'DateSent' => 'XsDateTime',
            'NumUniqueOpens' => 1,
            'NumUniqueTextOpens' => 1,
            'NumTotalUniqueOpens' => 1,
            'NumOpens' => 1,
            'NumTextOpens' => 1,
            'NumTotalOpens' => 1,
            'NumClicks' => 0,
            'NumTextClicks' => 0,
            'NumTotalClicks' => 1,
            'NumPageViews' => 1,
            'NumTotalPageViews' => 1,
            'NumTextPageViews' => 1,
            'NumForwards' => 1,
            'NumTextForwards' => 1,
            'NumEstimatedForwards' => 1,
            'NumTextEstimatedForwards' => 1,
            'NumTotalEstimatedForwards' => 1,
            'NumReplies' => 1,
            'NumTextReplies' => 1,
            'NumTotalReplies' => 1,
            'NumHardBounces' => 1,
            'NumTextHardBounces' => 1,
            'NumTotalHardBounces' => 1,
            'NumSoftBounces' => 1,
            'NumTextSoftBounces' => 1,
            'NumTotalSoftBounces' => 1,
            'NumUnsubscribes' => 1,
            'NumTextUnsubscribes' => 1,
            'NumTotalUnsubscribes' => 1,
            'NumIspComplaints' => 1,
            'NumTextIspComplaints' => 1,
            'NumTotalIspComplaints' => 1,
            'NumMailBlocks' => 1,
            'NumTextMailBlocks' => 1,
            'NumTotalMailBlocks' => 1,
            'NumSent' => 1,
            'NumTextSent' => 1,
            'NumTotalSent' => 1,
            'NumRecipientsClicked' => 1,
            'NumDelivered' => 1,
            'NumTextDelivered' => 1,
            'NumTotalDelivered' => 1,
            'PercentageDelivered' => 2.0,
            'PercentageUniqueOpens' => 2.0,
            'PercentageOpens' => 2.0,
            'PercentageUnsubscribes' => 2.0,
            'PercentageReplies' => 2.0,
            'PercentageHardBounces' => 2.0,
            'PercentageSoftBounces' => 2.0,
            'PercentageUsersClicked' => 2.0,
            'PercentageClicksToOpens' => 2.0,
            'Revenue' => '$0.00',
            'ConversionRate' => 2.0,
            'AssistedRevenue' => '$0.00',
            'NumOrders' => 1
        ];

        $summary = new ApiCampaignSummary($values);
        self::assertInstanceOf(ApiCampaignSummary::class, $summary);
    }
}
