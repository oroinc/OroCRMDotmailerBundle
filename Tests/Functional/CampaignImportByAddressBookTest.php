<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use DotMailer\Api\DataTypes\ApiCampaignList;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;

class CampaignImportByAddressBookTest extends AbstractImportExportTestCase
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
        $entity = new ApiCampaignList(
            [
                [
                    'id' => 15662,
                    'name' => 'NewsLetter',
                    'subject' => 'News Letter',
                    'fromname' => 'CityBeach',
                    'fromaddress' => [
                        'id' => 6141,
                        'email' => 'Arbitbet@dotmailer-email.com',
                    ],
                    'htmlcontent' => 'null',
                    'plaintextcontent' => 'null',
                    'replyaction' => 'Webmail',
                    'replytoaddress' => '',
                    'issplittest' => 'false',
                    'status' => 'Sent'
                ]
            ]
        );
        $this->resource->expects($this->any())
            ->method('GetAddressBookCampaigns')
            ->will($this->returnValue($entity));
        $channel = $this->getReference('oro_dotmailer.channel.first');
        $result = $this->runImportExportConnectorsJob(
            self::SYNC_PROCESSOR,
            $channel,
            CampaignConnector::TYPE,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $this->getReference(
                    'oro_dotmailer.address_book.second'
                )->getId()
            ]
        );

        $this->assertTrue($result);
    }
}
