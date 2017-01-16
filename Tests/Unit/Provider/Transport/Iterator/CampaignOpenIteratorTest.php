<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiCampaignContactOpenList;
use DotMailer\Api\DataTypes\ApiCampaignContactOpen;

use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivityType;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractActivityIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignOpenIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\AdditionalResource;

class CampaignOpenIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIteratorInitTrue()
    {
        $resource = $this->createMock('DotMailer\Api\Resources\IResources');
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $additionalResource = $this->getMockBuilder(AdditionalResource::class)
            ->disableOriginalConstructor()->getMock();
        $expectedCampaignOriginId = 15662;
        $expectedEmailCampaignId = 12;
        $expectedMarketingCampaignId = 1;
        $expectedDate = new \DateTime();
        $iterator = new CampaignOpenIterator(
            $resource,
            $registry,
            $expectedCampaignOriginId,
            $expectedEmailCampaignId,
            $expectedMarketingCampaignId,
            true,
            $expectedDate,
            $additionalResource
        );
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactOpenList();
        $expectedActivity = new ApiCampaignContactOpen();
        $expectedActivity->contactId = '123';
        $items[] = $expectedActivity;
        $additionalResource->expects($this->any())
            ->method('getCampaignOpensSinceDateByDate')
            ->with($expectedCampaignOriginId, $expectedDate->format(\DateTime::ISO8601))
            ->will($this->returnValueMap(
                [
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
                        new ApiCampaignContactOpenList()
                    ],
                ]
            ));

        $expectedData = [
            'originId' => '123',
            'entityClass' => 'entityClassName',
            'entityId' => 12
        ];
        $this->prepareRepositoryMock($registry, $expectedData);

        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[AbstractActivityIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY] =
                MarketingActivityType::TYPE_OPEN;
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
        $resource = $this->createMock('DotMailer\Api\Resources\IResources');
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $additionalResource = $this->getMockBuilder(AdditionalResource::class)
            ->disableOriginalConstructor()->getMock();
        $expectedCampaignOriginId = 15662;
        $expectedEmailCampaignId = 12;
        $expectedMarketingCampaignId = 1;
        $expectedDate = new \DateTime();
        $iterator = new CampaignOpenIterator(
            $resource,
            $registry,
            $expectedCampaignOriginId,
            $expectedEmailCampaignId,
            $expectedMarketingCampaignId,
            false,
            $expectedDate,
            $additionalResource
        );
        $iterator->setBatchSize(1);
        $items = new ApiCampaignContactOpenList();
        $expectedActivity = new ApiCampaignContactOpen();
        $expectedActivity->contactId = '123';
        $items[] = $expectedActivity;
        $resource->expects($this->any())
            ->method('GetCampaignOpens')
            ->with($expectedCampaignOriginId)
            ->will($this->returnValueMap(
                [
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
                        new ApiCampaignContactOpenList()
                    ],
                ]
            ));

        $expectedData = [
            'originId' => '123',
            'entityClass' => 'entityClassName',
            'entityId' => 12
        ];
        $this->prepareRepositoryMock($registry, $expectedData);

        foreach ($iterator as $item) {
            $expectedActivityContactArray = $expectedActivity->toArray();
            $expectedActivityContactArray[AbstractActivityIterator::CAMPAIGN_KEY] = $expectedCampaignOriginId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY] =
                MarketingActivityType::TYPE_OPEN;
            $expectedActivityContactArray[AbstractActivityIterator::EMAIL_CAMPAIGN_KEY] = $expectedEmailCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::MARKETING_CAMPAIGN_KEY] =
                $expectedMarketingCampaignId;
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_ID_KEY] = $expectedData['entityId'];
            $expectedActivityContactArray[AbstractActivityIterator::ENTITY_CLASS_KEY] = $expectedData['entityClass'];
            $this->assertSame($expectedActivityContactArray, $item);
        }
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $registry
     * @param array $expectedData
     */
    protected function prepareRepositoryMock($registry, $expectedData)
    {
        $repository = $this->getMockBuilder(ContactRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->at(0))
            ->method('getEntitiesDataByOriginIds')
            ->with([$expectedData['originId']])
            ->will($this->returnValue(
                [
                    [
                        'originId' => $expectedData['originId'],
                        'entityClass' => $expectedData['entityClass'],
                        'entityId' => $expectedData['entityId']
                    ]
                ]
            ));
        $repository->expects($this->at(1))
            ->method('getEntitiesDataByOriginIds')
            ->will($this->returnValue([]));

        $registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
    }
}
