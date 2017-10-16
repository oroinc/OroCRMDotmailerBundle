<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignContactSummaryList;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ActivityContactConnector;

class ActivityContactImportByAddressBookTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadActivityData',
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData',
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiCampaignContactSummaryList(
            [
                [
                    'email'                => 'alex.case@example.com',
                    'numopens'             => 3,
                    'numpageviews'         => 0,
                    'numclicks'            => 0,
                    'numforwards'          => 0,
                    'numestimatedforwards' => 2,
                    'numreplies'           => 0,
                    'datesent'             => '2015-04-15T13:48:33.013Z',
                    'datefirstopened'      => '2015-04-16T13:48:33.013Z',
                    'datelastopened'       => '2015-04-16T13:48:33.013Z',
                    'firstopenip'          => '61.249.92.173',
                    'unsubscribed'         => 'false',
                    'softbounced'          => 'false',
                    'hardbounced'          => 'false',
                    'contactid'            => 147,
                ],
            ]
        );
        $firstCampaignId = 15662;
        $this->resource->expects($this->once())
            ->method('GetCampaignActivitiesSinceDateByDate')
            ->with($firstCampaignId)
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.second');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            ActivityContactConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.second'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
