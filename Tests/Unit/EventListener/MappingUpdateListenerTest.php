<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\EventListener\MappingUpdateListener;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class MappingUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mappingProvider;

    /** @var MappingUpdateListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->mappingProvider = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Provider\MappingProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new MappingUpdateListener(
            $this->doctrineHelper,
            $this->mappingProvider
        );
    }

    public function testOnFlushWithInsertions()
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
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([
                $mappingConfig
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $addressBookContactRepository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->will($this->returnValue($addressBookContactRepository));

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateScheduledForEntityFieldUpdateFlag')
            ->with('MappingEntityClass', $channel);

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateEntityUpdatedFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushWithUpdateDataField()
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
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([
                $mappingConfig
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $unitOfWork->expects($this->once())->method('getEntityChangeSet')->with($mappingConfig)
            ->will($this->returnValue([
                'dataField' => [],
            ]));

        $addressBookContactRepository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->will($this->returnValue($addressBookContactRepository));

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateEntityUpdatedFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushWithUpdateTwoWaySync()
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
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([
                $mappingConfig
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $unitOfWork->expects($this->once())->method('getEntityChangeSet')->with($mappingConfig)
            ->will($this->returnValue([
                'isTwoWaySync' => [
                    0 => false,
                    1 => true
                ],
            ]));

        $addressBookContactRepository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')
            ->with('OroDotmailerBundle:AddressBookContact')
            ->will($this->returnValue($addressBookContactRepository));

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateScheduledForEntityFieldUpdateFlag')
            ->with('MappingEntityClass', $channel);

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);
    }

    public function testOnFlushAndPostFlushWithRemovedMapping()
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
        $channel = new Channel();
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setMapping($mapping);
        $mappingConfig->setIsTwoWaySync(true);
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue([
                $mappingConfig
            ]));

        $onFlushEvent = new OnFlushEventArgs($em);

        $this->listener->onFlush($onFlushEvent);

        $this->mappingProvider->expects($this->once())->method('clearCachedValues');
        $this->mappingProvider->expects($this->once())->method('getTrackedFieldsConfig');
        $postFlushEvent = new PostFlushEventArgs($em);
        $this->listener->postFlush($postFlushEvent);
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
}
