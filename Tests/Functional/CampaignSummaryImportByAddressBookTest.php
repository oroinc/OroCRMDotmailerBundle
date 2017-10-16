<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignSummary;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignSummaryConnector;

class CampaignSummaryImportByAddressBookTest extends AbstractImportExportTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData',
            ]
        );
    }

    public function testImport()
    {
        $entity = new ApiCampaignSummary([
            'numUniqueOpens'      => 5,
            'numUniqueTextOpens'  => 5,
            'numTotalUniqueOpens' => 5,
            'numOpens'            => 5,
            'numTextOpens'        => 5,
            'numTotalOpens'       => 5,
            'numClicks'           => 5,
            'numTextClicks'       => 5,
            'numTotalClicks'      => 5,
        ]);

        $this->resource->expects($this->any())
            ->method('GetCampaignSummary')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.second');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignSummaryConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.second'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
