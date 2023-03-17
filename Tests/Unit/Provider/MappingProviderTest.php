<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\DotmailerBundle\Provider\MappingTrackedFieldsEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MappingProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldsProvider;

    /** @var MappingProvider */
    private $mappingProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(AbstractAdapter::class);
        $this->virtualFieldsProvider = $this->createMock(VirtualFieldProviderInterface::class);

        $this->mappingProvider = new MappingProvider(
            $this->doctrineHelper,
            $this->cache,
            $this->virtualFieldsProvider
        );
    }

    public function testGetTwoWaySyncFieldsForEntityNoCache(): void
    {
        $cacheKey = 'two_way_sync_entity_1';
        $repository = $this->getRepository();
        $mapping = ['field' => 'datafield'];
        $repository->expects($this->once())
            ->method('getTwoWaySyncFieldsForEntity')
            ->with('entity', 1)
            ->willReturn($mapping);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('entity')
            ->willReturn('id');

        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getTwoWaySyncFieldsForEntity('entity', 1);

        $this->assertEquals(
            [
                'field' => 'datafield',
                'entityId' => 'id'
            ],
            $result
        );
    }

    public function testGetTwoWaySyncFieldsForEntityCached(): void
    {
        $cacheKey = 'two_way_sync_entity_1';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['field' => 'datafield']);
        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifierFieldName');

        $result = $this->mappingProvider->getTwoWaySyncFieldsForEntity('entity', 1);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetExportMappingConfigForEntityNoCache(): void
    {
        $cacheKey = 'export_entity_1';
        $repository = $this->getRepository();
        $mapping = ['field' => 'datafield'];
        $repository->expects($this->once())
            ->method('getMappingConfigForEntity')
            ->with('entity', 1)
            ->willReturn($mapping);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getExportMappingConfigForEntity('entity', 1);

        $this->assertEquals(
            [
                'field' => 'datafield',
            ],
            $result
        );
    }

    public function testGetExportMappingConfigForEntityCached(): void
    {
        $cacheKey = 'export_entity_1';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['field' => 'datafield']);

        $result = $this->mappingProvider->getExportMappingConfigForEntity('entity', 1);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetDataFieldMappingBySyncPriorityNoCache(): void
    {
        $cacheKey = 'prioritized_1';
        $repository = $this->getRepository();
        $mappings = [
            [
                'dataFieldName' => 'datafield',
                'entity' => 'entityClass',
                'syncPriority' => 10
            ],
            [
                'dataFieldName' => 'datafield',
                'entity' => 'anotherEntityClass',
                'syncPriority' => 20
            ],
            [
                'dataFieldName' => 'datafield2',
                'entity' => 'entityClass',
                'syncPriority' => 10
            ]
        ];
        $channel = $this->getChannel(1);
        $repository->expects($this->once())
            ->method('getMappingBySyncPriority')
            ->with($channel)
            ->willReturn($mappings);
        $expected = [
            'datafield' => [
                'entityClass' => 10,
                'anotherEntityClass' => 20
            ],
            'datafield2' => [
                'entityClass' => 10
            ]
        ];
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getDataFieldMappingBySyncPriority($channel);

        $this->assertEquals($expected, $result);
    }

    public function testGetDataFieldMappingBySyncPriorityCached(): void
    {
        $cacheKey = 'prioritized_1';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['field' => 'datafield']);
        $channel = $this->getChannel(1);
        $result = $this->mappingProvider->getDataFieldMappingBySyncPriority($channel);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetEntitiesQualifiedForTwoWaySyncNoCache(): void
    {
        $cacheKey = 'two_way_sync_entities_1';
        $repository = $this->getRepository();
        $entities = ['testEntity'];
        $channel = $this->getChannel(1);
        $repository->expects($this->once())
            ->method('getEntitiesQualifiedForTwoWaySync')
            ->with($channel)
            ->willReturn($entities);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getEntitiesQualifiedForTwoWaySync($channel);

        $this->assertEquals(
            ['testEntity'],
            $result
        );
    }

    public function testGetEntitiesQualifiedForTwoWaySyncCached(): void
    {
        $cacheKey = 'two_way_sync_entities_1';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['field' => 'datafield']);
        $channel = $this->getChannel(1);
        $result = $this->mappingProvider->getEntitiesQualifiedForTwoWaySync($channel);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetTrackedFieldsConfigNoCache(): void
    {
        $cacheKey = 'tracked_fields';
        $repository = $this->getRepository();
        $mappings = [];
        $channel = $this->getChannel(1);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('firstName,lastName');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $mapping = new DataFieldMapping();
        $mapping->setEntity('AnotherEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('addresses+Oro\Bundle\AkmeBundle\Entity\Address::postalCode');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($mappings);
        $expected = [
            'MappingEntityClass' => [
                'firstName' => [
                    [
                        'channel_id' => 1,
                        'parent_entity' => 'MappingEntityClass',
                        'field_path' => 'firstName'
                    ]
                ],
                'lastName' => [
                    [
                        'channel_id' => 1,
                        'parent_entity' => 'MappingEntityClass',
                        'field_path' => 'lastName'
                    ]
                ]
            ],
            'Oro\Bundle\AkmeBundle\Entity\Address' => [
                'postalCode' => [
                    [
                    'channel_id' => 1,
                    'parent_entity' => 'AnotherEntityClass',
                    'field_path' => 'addresses+Oro\Bundle\AkmeBundle\Entity\Address::postalCode'
                    ]
                ],
            ]
        ];
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getTrackedFieldsConfig();

        $this->assertEquals($expected, $result);
    }

    public function testGetTrackedFieldsConfigModifiedInEvent(): void
    {
        $cacheKey = 'tracked_fields';
        $repository = $this->getRepository();
        $mappings = [];
        $channel = $this->getChannel(1);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('firstName');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($mappings);

        $eventData = [
            'MappingEntityClass' => [
                'firstName' => [
                    [
                        'channel_id' => 1,
                        'parent_entity' => 'MappingEntityClass',
                        'field_path' => 'firstName'
                    ]
                ],
                'twitter' => [
                    [
                        'channel_id' => 1,
                        'parent_entity' => 'MappingEntityClass',
                        'field_path' => 'twitter'
                    ]
                ],
            ]
        ];

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(MappingTrackedFieldsEvent::NAME)
            ->willReturn(true);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(MappingTrackedFieldsEvent::class),
                MappingTrackedFieldsEvent::NAME
            )
            ->willReturnCallback(function (MappingTrackedFieldsEvent $event) use ($eventData) {
                $event->setFields($eventData);

                return $event;
            });
        $this->mappingProvider->setDispatcher($dispatcher);

        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->mappingProvider->getTrackedFieldsConfig();

        $this->assertEquals($eventData, $result);
    }

    public function testEntityHasVirutalFieldsMapped(): void
    {
        $cacheKey = 'tracked_fields';
        $repository = $this->getRepository();
        $mappings = [];
        $channel = $this->getChannel(1);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('primaryPhone');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($mappings);
        $this->virtualFieldsProvider->expects($this->once())
            ->method('isVirtualField')
            ->with('MappingEntityClass', 'primaryPhone')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->assertTrue($this->mappingProvider->entityHasVirutalFieldsMapped(1, 'MappingEntityClass'));
    }

    public function testGetTrackedFieldsConfigCached(): void
    {
        $cacheKey = 'tracked_fields';
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['array']);
        $result = $this->mappingProvider->getTrackedFieldsConfig();
        $this->assertEquals(['array'], $result);
    }

    public function testClearCachedValues(): void
    {
        $mappings = [];
        $channel = $this->getChannel(1);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappings[] = $mapping;
        $repository = $this->getRepository();
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($mappings);
        $this->cache->expects($this->exactly(5))
            ->method('delete')
            ->withConsecutive(
                ['tracked_fields'],
                ['two_way_sync_entities_1'],
                ['prioritized_1'],
                ['export_MappingEntityClass_1'],
                ['two_way_sync_MappingEntityClass_1']
            );
        $this->mappingProvider->clearCachedValues();
    }

    private function getRepository(): DataFieldMappingRepository|\PHPUnit\Framework\MockObject\MockObject
    {
        $repository = $this->createMock(DataFieldMappingRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(DataFieldMapping::class)
            ->willReturn($repository);

        return $repository;
    }

    private function getChannel(int $id): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);

        return $channel;
    }
}
