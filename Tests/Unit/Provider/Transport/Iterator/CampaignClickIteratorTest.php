<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use DotMailer\Api\DataTypes\ApiCampaignContactClick;
use DotMailer\Api\DataTypes\ApiCampaignContactClickList;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Provider\Transport\AdditionalResource;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractActivityIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignClickIterator;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class CampaignClickIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIteratorInitTrue()
    {
        $resource = $this->createMock(IResources::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $additionalResource = $this->createMock(AdditionalResource::class);
        $expectedCampaignOriginId = 15662;
        $expectedEmailCampaignId = 12;
        $expectedMarketingCampaignId = 1;
        $addressBooks = [1];
        $expectedDate = new \DateTime();
        $iterator = new CampaignClickIterator(
            $resource,
            $registry,
            $expectedCampaignOriginId,
            $expectedEmailCampaignId,
            $expectedMarketingCampaignId,
            $addressBooks,
            true,
            $expectedDate,
            $additionalResource
        );
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactClickList();
        $expectedActivity = new ApiCampaignContactClick();
        $expectedActivity->contactId = '123';
        $items[] = $expectedActivity;

        $additionalResource->expects($this->any())
            ->method('getCampaignClicksSinceDateByDate')
            ->with($expectedCampaignOriginId, $expectedDate->format(\DateTime::ISO8601))
            ->willReturnMap([
                [
                    $expectedCampaignOriginId,
                    $expectedDate->format(\DateTime::ISO8601),
                    1,
                    0,
                    $items
                ],
                [
                    $expectedCampaignOriginId,
                    $expectedDate->format(\DateTime::ISO8601),
                    1,
                    1,
                    new ApiCampaignContactClickList()
                ],
            ]);

        $expectedData = [
            'originId' => '123',
            'entityClass' => 'entityClassName',
            'entityId' => 12,
            'addressBooks' => $addressBooks
        ];
        $this->prepareRepository($registry, $expectedData);

        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[AbstractActivityIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY] =
                MarketingActivity::TYPE_CLICK;
            $expectedActivityContactArray[AbstractActivityIterator::EMAIL_CAMPAIGN_KEY] = $expectedEmailCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_CAMPAIGN_KEY] =
                $expectedMarketingCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_ID_KEY] = $expectedData['entityId'];
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_CLASS_KEY] = $expectedData['entityClass'];
            $this->assertSame($expectedActivityContactArray, $item);
        }
    }

    public function testIteratorInitFalse()
    {
        $resource = $this->createMock(IResources::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $additionalResource = $this->createMock(AdditionalResource::class);
        $expectedCampaignOriginId = 15662;
        $expectedEmailCampaignId = 12;
        $expectedMarketingCampaignId = 1;
        $addressBooks = [1];
        $expectedDate = new \DateTime();
        $iterator = new CampaignClickIterator(
            $resource,
            $registry,
            $expectedCampaignOriginId,
            $expectedEmailCampaignId,
            $expectedMarketingCampaignId,
            $addressBooks,
            false,
            $expectedDate,
            $additionalResource
        );
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactClickList();
        $expectedActivity = new ApiCampaignContactClick();
        $expectedActivity->contactId = '123';
        $items[] = $expectedActivity;

        $resource->expects($this->any())
            ->method('GetCampaignClicks')
            ->with($expectedCampaignOriginId)
            ->willReturnMap([
                [
                    $expectedCampaignOriginId,
                    1,
                    0,
                    $items
                ],
                [
                    $expectedCampaignOriginId,
                    1,
                    1,
                    new ApiCampaignContactClickList()
                ],
            ]);

        $expectedData = [
            'originId' => '123',
            'entityClass' => 'entityClassName',
            'entityId' => 12,
            'addressBooks' => $addressBooks
        ];
        $this->prepareRepository($registry, $expectedData);

        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[AbstractActivityIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY] =
                MarketingActivity::TYPE_CLICK;
            $expectedActivityContactArray[AbstractActivityIterator::EMAIL_CAMPAIGN_KEY] = $expectedEmailCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_CAMPAIGN_KEY] =
                $expectedMarketingCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_ID_KEY] = $expectedData['entityId'];
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_CLASS_KEY] = $expectedData['entityClass'];
            $this->assertSame($expectedActivityContactArray, $item);
        }
    }

    private function prepareRepository(
        ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry,
        array $expectedData
    ): void {
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->once())
            ->method('getEntitiesDataByOriginIds')
            ->with([$expectedData['originId']], $expectedData['addressBooks'])
            ->willReturn([
                [
                    'originId'    => $expectedData['originId'],
                    'entityClass' => $expectedData['entityClass'],
                    'entityId'    => $expectedData['entityId']
                ]
            ]);

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
    }
}
