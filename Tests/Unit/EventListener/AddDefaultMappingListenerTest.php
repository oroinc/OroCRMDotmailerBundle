<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldRepository;
use Oro\Bundle\DotmailerBundle\EventListener\AddDefaultMappingListener;
use Oro\Bundle\DotmailerBundle\EventListener\MappingUpdateListener;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;

class AddDefaultMappingListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityProvider;

    /** @var DefaultOwnerHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerHelper;

    /** @var MappingUpdateListener|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingListener;

    /** @var MappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var AddDefaultMappingListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $this->ownerHelper = $this->createMock(DefaultOwnerHelper::class);
        $this->mappingListener = $this->createMock(MappingUpdateListener::class);
        $this->mappingProvider = $this->createMock(MappingProvider::class);

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
            ['import' => ['channel' => 1]],
            $jobResult
        );
        $channelRepository = $this->createMock(ChannelRepository::class);
        $dataFieldRepository = $this->createMock(DataFieldRepository::class);
        $dataFieldMappingRepository = $this->createMock(DataFieldMappingRepository::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroIntegrationBundle:Channel', null, $channelRepository],
                ['OroDotmailerBundle:DataField', null, $dataFieldRepository],
                ['OroDotmailerBundle:DataFieldMapping', null, $dataFieldMappingRepository]
            ]);
        $channel = new Channel();
        $channelRepository->expects($this->any())
            ->method('getOrLoadById')
            ->with(1)
            ->willReturn($channel);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $jobsCount = 0;
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($jobsCount);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $channelRepository->expects($this->once())
            ->method('getConnectorStatusesQueryBuilder')
            ->with($channel, DataFieldConnector::TYPE, Status::STATUS_COMPLETED)
            ->willReturn($queryBuilder);

        $dataField = new DataField();
        $dataFieldRepository->expects($this->once())
            ->method('getChannelDataFieldByNames')
            ->with(['FIRSTNAME', 'LASTNAME', 'FULLNAME'], $channel)
            ->willReturn([
                'FIRSTNAME' => $dataField,
                'LASTNAME'  => $dataField,
                'FULLNAME'  => $dataField
            ]);

        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $entities = [
            ['name' => 'LeadClass'],
            ['name' => 'ContactClass'],
            ['name' => 'CustomerClass']
        ];
        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->willReturn($entities);

        $dataFieldMappingRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnMap([
                [['channel' => $channel, 'entity' => 'LeadClass'], null, 'found object'], //mapping already exists
                [['channel' => $channel, 'entity' => 'ContactClass'], null, null],
                [['channel' => $channel, 'entity' => 'CustomerClass'], null, null],
            ]);

        $this->ownerHelper->expects($this->exactly(2))
            ->method('populateChannelOwner');

        $contactMetadata = $this->createMock(ClassMetadata::class);
        $customerMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturnMap([
                ['ContactClass', true, $contactMetadata],
                ['CustomerClass', true, $customerMetadata]
            ]);
        $contactFieldNames = ['firstName', 'lastName'];
        $customerFieldNames = ['field', 'anotherField']; //no fields for mapping available
        $contactMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn($contactFieldNames);
        $customerMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn($customerFieldNames);

        $manager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($mapping) use ($channel) {
                $this->assertInstanceOf(DataFieldMapping::class, $mapping);
                $this->assertEquals('ContactClass', $mapping->getEntity());
                $this->assertSame($channel, $mapping->getChannel());
                $this->assertCount(3, $mapping->getConfigs());

                return true;
            }));
        $manager->expects($this->once())
            ->method('flush');
        $this->mappingProvider->expects($this->once())
            ->method('clearCachedValues');
        $this->mappingListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->listener->afterSyncFinished($syncEvent);
    }

    public function testAfterSyncFinishedWhenNotApplicableJobFailed()
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);
        $syncEvent = new SyncEvent(
            DataFieldConnector::IMPORT_JOB,
            ['import' => ['channel' => 1]],
            $jobResult
        );
        $this->registry->expects($this->never())
            ->method('getManager');
        $this->listener->afterSyncFinished($syncEvent);
    }

    public function testAfterSyncFinishedWhenNotApplicableNotFirstJob()
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(true);
        $syncEvent = new SyncEvent(
            DataFieldConnector::IMPORT_JOB,
            ['import' => ['channel' => 1]],
            $jobResult
        );
        $channelRepository = $this->createMock(ChannelRepository::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroIntegrationBundle:Channel', null, $channelRepository],
            ]);
        $channel = new Channel();
        $channelRepository->expects($this->any())
            ->method('getOrLoadById')
            ->with(1)
            ->willReturn($channel);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $jobsCount = 10;
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($jobsCount);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $channelRepository->expects($this->once())
            ->method('getConnectorStatusesQueryBuilder')
            ->with($channel, DataFieldConnector::TYPE, Status::STATUS_COMPLETED)
            ->willReturn($queryBuilder);

        $this->registry->expects($this->never())
            ->method('getManager');

        $this->listener->afterSyncFinished($syncEvent);
    }
}
