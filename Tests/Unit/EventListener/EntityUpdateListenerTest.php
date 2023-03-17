<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ChangedFieldLogRepository;
use Oro\Bundle\DotmailerBundle\EventListener\EntityUpdateListener;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var EntityUpdateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->mappingProvider = $this->createMock(MappingProvider::class);

        $this->listener = new EntityUpdateListener(
            $this->doctrineHelper,
            $this->mappingProvider
        );
    }

    public function testOnFlush()
    {
        $entityWithTrackedFields = new \stdClass();
        $entityWithoutTrackedFields = new \stdClass();

        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entityWithTrackedFields, $entityWithoutTrackedFields]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->mappingProvider->expects($this->once())
            ->method('getTrackedFieldsConfig')
            ->willReturn([
                'entityClassWithTrackedFields' => [
                    'trackedField'        => [
                        [
                            'channel_id'    => 1,
                            'parent_entity' => 'parentEntityClass',
                            'field_path'    => 'trackedFieldPath'
                        ]
                    ],
                    'anotherTrackedField' => [
                        [
                            'channel_id'    => 2,
                            'parent_entity' => 'anotherParentEntityClass',
                            'field_path'    => 'anotherTrackedFieldPath'
                        ]
                    ]
                ]
            ]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) use ($entityWithTrackedFields, $entityWithoutTrackedFields) {
                if ($entity === $entityWithTrackedFields) {
                    return 'entityClassWithTrackedFields';
                }
                if ($entity === $entityWithoutTrackedFields) {
                    return 'entityClassWithoutTrackedFields';
                }
                throw new \LogicException('Unexpected entity');
            });

        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entityWithTrackedFields))
            ->willReturn([
                'trackedField'        => [],
                'anotherChangedField' => []
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entityWithTrackedFields), false)
            ->willReturn(42);

        $metaData = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(ChangedFieldLog::class)
            ->willReturn($metaData);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($log) {
                $this->assertInstanceOf(ChangedFieldLog::class, $log);
                $this->assertEquals(1, $log->getChannelId());
                $this->assertEquals('parentEntityClass', $log->getParentEntity());
                $this->assertEquals('trackedFieldPath', $log->getRelatedFieldPath());
                $this->assertEquals(42, $log->getRelatedId());

                return true;
            }));
        $unitOfWork->expects($this->once())
            ->method('computeChangeSet');
        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushNotExecutedWhenDisabled()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getUnitOfWork');
        $onFlushEvent = new OnFlushEventArgs($em);
        $this->listener->setEnabled(false);
        $this->listener->onFlush($onFlushEvent);
    }

    public function testPostFlush()
    {
        $insertedEntity = new \stdClass();

        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$insertedEntity]);
        $unitOfWork->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($this->identicalTo($insertedEntity))
            ->willReturn(true);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->mappingProvider->expects($this->once())
            ->method('getTrackedFieldsConfig')
            ->willReturn([
                'insertedEntityClass' => [
                    'trackedField' => [
                        [
                            'channel_id'    => 1,
                            'parent_entity' => 'parentEntityClass',
                            'field_path'    => 'trackedFieldPath'
                        ]
                    ]
                ]
            ]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) use ($insertedEntity) {
                if ($entity === $insertedEntity) {
                    return 'insertedEntityClass';
                }
                throw new \LogicException('Unexpected entity');
            });

        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($insertedEntity))
            ->willReturn([
                'trackedField' => []
            ]);

        $metaData = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(ChangedFieldLog::class)
            ->willReturn($metaData);

        $this->listener->onFlush($onFlushEvent);
        $onPostFlushEvent = new PostFlushEventArgs($em);
        $repository = $this->createMock(ChangedFieldLogRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ChangedFieldLog::class)
            ->willReturn($repository);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($insertedEntity), false)
            ->willReturn(42);
        $repository->expects($this->once())
            ->method('addEntityIdToLog')
            ->with(42, null);
        $this->listener->postFlush($onPostFlushEvent);
    }

    public function testPostFlushNotExecutedWhenDisabled()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getRepository');
        $onFlushEvent = new PostFlushEventArgs($em);
        $this->listener->setEnabled(false);
        $this->listener->postFlush($onFlushEvent);
    }
}
