<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use OroCRM\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use OroCRM\Bundle\DotmailerBundle\Entity\Repository\ChangedFieldLogRepository;
use OroCRM\Bundle\DotmailerBundle\EventListener\EntityUpdateListener;

class EntityUpdateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mappingProvider;

    /** @var EntityUpdateListener */
    protected $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->mappingProvider = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Provider\MappingProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new EntityUpdateListener(
            $this->doctrineHelper,
            $this->mappingProvider
        );
    }

    public function testOnFlush()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $unitOfWork = $this
            ->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([
                'entityClassWithTrackedFields',
                'entityClassWithoutTrackedFields',
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->mappingProvider->expects($this->once())->method('getTrackedFieldsConfig')->will($this->returnValue(
            [
                'entityClassWithTrackedFields' => [
                    'trackedField' => [
                        [
                            'channel_id' => 1,
                            'parent_entity' => 'parentEntityClass',
                            'field_path' => 'trackedFieldPath'
                        ]
                    ],
                    'anotherTrackedField' => [
                        [
                            'channel_id' => 2,
                            'parent_entity' => 'anotherParentEntityClass',
                            'field_path' => 'anotherTrackedFieldPath'
                        ]
                    ]
                ]
            ]
        ));
        
        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->will($this->returnArgument(0));

        $unitOfWork->expects($this->once())->method('getEntityChangeSet')->with('entityClassWithTrackedFields')
            ->will($this->returnValue([
                'trackedField' => [],
                'anotherChangedField' => []
            ]));
        
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')
            ->with('entityClassWithTrackedFields', false)
            ->will($this->returnValue(42));

        $metaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($metaData));
        
        
        $em->expects($this->once())->method('persist')->with($this->callback(
            function ($log) {
                $this->assertInstanceOf(ChangedFieldLog::class, $log);
                /** @var $log ChangedFieldLog */
                $this->assertEquals(1, $log->getChannelId());
                $this->assertEquals('parentEntityClass', $log->getParentEntity());
                $this->assertEquals('trackedFieldPath', $log->getRelatedFieldPath());
                $this->assertEquals(42, $log->getRelatedId());
                return true;
            }
        ));
        $unitOfWork->expects($this->once())->method('computeChangeSet');
        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushNotExecutedWhenDisabled()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $em->expects($this->never())->method('getUnitOfWork');
        $onFlushEvent = new OnFlushEventArgs($em);
        $this->listener->setEnabled(false);
        $this->listener->onFlush($onFlushEvent);
    }

    public function testPostFlush()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $unitOfWork = $this
            ->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([
                'insertedEntityClass',
            ]));
        $unitOfWork->expects($this->once())->method('isScheduledForInsert')->with('insertedEntityClass')
            ->will($this->returnValue(true));

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->mappingProvider->expects($this->once())->method('getTrackedFieldsConfig')->will($this->returnValue(
            [
                'insertedEntityClass' => [
                    'trackedField' => [
                        [
                            'channel_id' => 1,
                            'parent_entity' => 'parentEntityClass',
                            'field_path' => 'trackedFieldPath'
                        ]
                    ],
                ]
            ]
        ));
        
        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->will($this->returnArgument(0));

        $unitOfWork->expects($this->once())->method('getEntityChangeSet')->with('insertedEntityClass')
            ->will($this->returnValue([
                'trackedField' => []
            ]));
        
        $metaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadataForClass')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($metaData));

        $this->listener->onFlush($onFlushEvent);
        $onPostFlushEvent = new PostFlushEventArgs($em);
        $repository = $this
            ->getMockBuilder(ChangedFieldLogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getRepository')->with(ChangedFieldLog::class)
            ->will($this->returnValue($repository));
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')
            ->with('insertedEntityClass', false)->will($this->returnValue(42));
        $repository->expects($this->once())->method('addEntityIdToLog')->with(42, null);
        $this->listener->postFlush($onPostFlushEvent);
    }

    public function testPostFlushNotExecutedWhenDisabled()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $em->expects($this->never())->method('getRepository');
        $onFlushEvent = new PostFlushEventArgs($em);
        $this->listener->setEnabled(false);
        $this->listener->postFlush($onFlushEvent);
    }
}
