<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\EventListener\AddDefaultMappingListener;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

class AddDefaultMappingListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $ownerHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mappingListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mappingProvider;

    /** @var AddDefaultMappingListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->entityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->ownerHelper = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper')
            ->disableOriginalConstructor()->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->getMock();
        $this->mappingListener = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\EventListener\MappingUpdateListener')
            ->disableOriginalConstructor()->getMock();
        $this->mappingProvider = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Provider\MappingProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new AddDefaultMappingListener(
            $this->registry,
            $this->doctrineHelper,
            $this->entityProvider,
            $this->ownerHelper,
            $this->mappingListener,
            $this->mappingProvider
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterSyncFinishedWhenApplicable()
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(true);
        $syncEvent = new SyncEvent(
            DataFieldConnector::IMPORT_JOB,
            [
                'import' => [
                    'channel' => 1
                ]
            ],
            $jobResult
        );
        $channelRepository = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()->getMock();
        $dataFieldRepository = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldRepository')
            ->disableOriginalConstructor()->getMock();
        $dataFieldMappingRepository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository')
            ->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->any())->method('getRepository')->will($this->returnValueMap(
            [
                ['OroIntegrationBundle:Channel', null, $channelRepository],
                ['OroDotmailerBundle:DataField', null, $dataFieldRepository],
                ['OroDotmailerBundle:DataFieldMapping', null, $dataFieldMappingRepository]
            ]
        ));
        $channel = new Channel();
        $channelRepository->expects($this->any())->method('getOrLoadById')->with(1)->will($this->returnValue($channel));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                            ->disableOriginalConstructor()->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
                            ->disableOriginalConstructor()->getMock();
        $jobsCount = 0;
        $query->expects($this->once())->method('getSingleScalarResult')->will($this->returnValue($jobsCount));
        $queryBuilder->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $channelRepository->expects($this->once())->method('getConnectorStatusesQueryBuilder')->with(
            $channel,
            DataFieldConnector::TYPE,
            Status::STATUS_COMPLETED
        )->will($this->returnValue($queryBuilder));

        $dataField = new DataField();
        $dataFieldRepository->expects($this->once())->method('getChannelDataFieldByNames')->with(
            ['FIRSTNAME', 'LASTNAME', 'FULLNAME'],
            $channel
        )->will($this->returnValue(
            [
                'FIRSTNAME' => $dataField,
                'LASTNAME' => $dataField,
                'FULLNAME' => $dataField
            ]
        ));

        $manager = $this->getMockBuilder('Doctrine\Persistence\ObjectManager')
                    ->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->once())->method('getManager')->will($this->returnValue($manager));

        $entities = [
            [
                'name' => 'LeadClass'
            ],
            [
                'name' => 'ContactClass'
            ],
            [
                'name' => 'CustomerClass'
            ]
        ];
        $this->entityProvider->expects($this->once())->method('getEntities')->will($this->returnValue($entities));

        $dataFieldMappingRepository->expects($this->any())->method('findOneBy')->will($this->returnValueMap(
            [
                [['channel' => $channel, 'entity' => 'LeadClass'], null, 'found object'], //mapping already exists
                [['channel' => $channel, 'entity' => 'ContactClass'], null, null],
                [['channel' => $channel, 'entity' => 'CustomerClass'], null, null],
            ]
        ));

        $this->ownerHelper->expects($this->exactly(2))->method('populateChannelOwner');

        $contactMetadata = $this->createMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $customerMetadata = $this->createMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $this->doctrineHelper->expects($this->any())->method('getEntityMetadata')->will($this->returnValueMap(
            [
                ['ContactClass', true, $contactMetadata],
                ['CustomerClass', true, $customerMetadata]
            ]
        ));
        $contactFieldNames = ['firstName', 'lastName'];
        $customerFieldNames = ['field', 'anotherField']; //no fields for mapping available
        $contactMetadata->expects($this->once())->method('getFieldNames')
            ->will($this->returnValue($contactFieldNames));
        $customerMetadata->expects($this->once())->method('getFieldNames')
            ->will($this->returnValue($customerFieldNames));

        $manager->expects($this->once())->method('persist')->with(
            $this->callback(function ($mapping) use ($channel) {
                /** @var $mapping DataFieldMapping */
                $this->assertTrue($mapping instanceof DataFieldMapping);
                $this->assertEquals('ContactClass', $mapping->getEntity());
                $this->assertSame($channel, $mapping->getChannel());
                $this->assertCount(3, $mapping->getConfigs());
                return true;
            })
        );
        $manager->expects($this->once())->method('flush');
        $this->mappingProvider->expects($this->once())->method('clearCachedValues');
        $this->mappingListener->expects($this->at(0))->method('setEnabled')->with(false);
        $this->mappingListener->expects($this->at(1))->method('setEnabled')->with(true);

        $this->listener->afterSyncFinished($syncEvent);
    }

    public function testAfterSyncFinishedWhenNotApplicableJobFailed()
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);
        $syncEvent = new SyncEvent(
            DataFieldConnector::IMPORT_JOB,
            [
                'import' => [
                    'channel' => 1
                ]
            ],
            $jobResult
        );
        $this->registry->expects($this->never())->method('getManager');
        $this->listener->afterSyncFinished($syncEvent);
    }

    public function testAfterSyncFinishedWhenNotApplicableNotFirstJob()
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(true);
        $syncEvent = new SyncEvent(
            DataFieldConnector::IMPORT_JOB,
            [
                'import' => [
                    'channel' => 1
                ]
            ],
            $jobResult
        );
        $channelRepository = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->any())->method('getRepository')->will($this->returnValueMap(
            [
                ['OroIntegrationBundle:Channel', null, $channelRepository],
            ]
        ));
        $channel = new Channel();
        $channelRepository->expects($this->any())->method('getOrLoadById')->with(1)->will($this->returnValue($channel));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                            ->disableOriginalConstructor()->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
                            ->disableOriginalConstructor()->getMock();
        $jobsCount = 10;
        $query->expects($this->once())->method('getSingleScalarResult')->will($this->returnValue($jobsCount));
        $queryBuilder->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $channelRepository->expects($this->once())->method('getConnectorStatusesQueryBuilder')->with(
            $channel,
            DataFieldConnector::TYPE,
            Status::STATUS_COMPLETED
        )->will($this->returnValue($queryBuilder));

        $this->registry->expects($this->never())->method('getManager');

        $this->listener->afterSyncFinished($syncEvent);
    }
}
