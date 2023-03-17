<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\DotmailerBundle\EventListener\MappingUpdateListener;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class MappingUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var MappingUpdateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->mappingProvider = $this->createMock(MappingProvider::class);

        $this->listener = new MappingUpdateListener(
            $this->doctrineHelper,
            $this->mappingProvider
        );
    }

    public function testOnFlushWithInsertions()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$mappingConfig]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->willReturn($addressBookContactRepository);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateScheduledForEntityFieldUpdateFlag')
            ->with('MappingEntityClass', $channel);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateEntityUpdatedFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushWithUpdateDataField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$mappingConfig]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($mappingConfig)
            ->willReturn(['dataField' => []]);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->willReturn($addressBookContactRepository);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateEntityUpdatedFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushWithUpdateTwoWaySync()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$mappingConfig]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($mappingConfig)
            ->willReturn(['isTwoWaySync' => [0 => false, 1 => true]]);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->willReturn($addressBookContactRepository);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateScheduledForEntityFieldUpdateFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushAndPostFlushWithRemovedMapping()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$mappingConfig]);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);

        $this->mappingProvider->expects($this->once())
            ->method('clearCachedValues');
        $this->mappingProvider->expects($this->once())
            ->method('getTrackedFieldsConfig');
        $postFlushEvent = new PostFlushEventArgs($em);
        $this->listener->postFlush($postFlushEvent);
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
}
