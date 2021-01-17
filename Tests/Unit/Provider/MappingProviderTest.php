<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\DotmailerBundle\Provider\MappingTrackedFieldsEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MappingProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $virtualFieldsProvider;

    /**
     * @var MappingProvider
     */
    protected $mappingProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(CacheProvider::class);
        $this->virtualFieldsProvider = $this->createMock(VirtualFieldProviderInterface::class);

        $this->mappingProvider = new MappingProvider(
            $this->doctrineHelper,
            $this->cache,
            $this->virtualFieldsProvider
        );
    }

    public function testGetTwoWaySyncFieldsForEntityNoCache()
    {
        $cacheKey = 'two_way_sync_entity_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $mapping = ['field' => 'datafield'];
        $repository->expects($this->once())->method('getTwoWaySyncFieldsForEntity')->with('entity', 1)->will(
            $this->returnValue($mapping)
        );
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifierFieldName')->with('entity')
            ->will($this->returnValue('id'));

        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            [
                'field' => 'datafield',
                'entityId' => 'id'
            ]
        );

        $result = $this->mappingProvider->getTwoWaySyncFieldsForEntity('entity', 1);

        $this->assertEquals(
            [
                'field' => 'datafield',
                'entityId' => 'id'
            ],
            $result
        );
    }

    public function testGetTwoWaySyncFieldsForEntityCached()
    {
        $cacheKey = 'two_way_sync_entity_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(['field' => 'datafield']));
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $result = $this->mappingProvider->getTwoWaySyncFieldsForEntity('entity', 1);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetExportMappingConfigForEntityNoCache()
    {
        $cacheKey = 'export_entity_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $mapping = ['field' => 'datafield'];
        $repository->expects($this->once())->method('getMappingConfigForEntity')->with('entity', 1)->will(
            $this->returnValue($mapping)
        );
        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            [
                'field' => 'datafield',
            ]
        );

        $result = $this->mappingProvider->getExportMappingConfigForEntity('entity', 1);

        $this->assertEquals(
            [
                'field' => 'datafield',
            ],
            $result
        );
    }

    public function testGetExportMappingConfigForEntityCached()
    {
        $cacheKey = 'export_entity_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(['field' => 'datafield']));

        $result = $this->mappingProvider->getExportMappingConfigForEntity('entity', 1);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetDataFieldMappingBySyncPriorityNoCache()
    {
        $cacheKey = 'prioritized_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
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
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $repository->expects($this->once())->method('getMappingBySyncPriority')->with($channel)->will(
            $this->returnValue($mappings)
        );
        $expected = [
            'datafield' => [
                'entityClass' => 10,
                'anotherEntityClass' => 20
            ],
            'datafield2' => [
                'entityClass' => 10
            ]
        ];
        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            $expected
        );

        $result = $this->mappingProvider->getDataFieldMappingBySyncPriority($channel);

        $this->assertEquals($expected, $result);
    }

    public function testGetDataFieldMappingBySyncPriorityCached()
    {
        $cacheKey = 'prioritized_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(['field' => 'datafield']));
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $result = $this->mappingProvider->getDataFieldMappingBySyncPriority($channel);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetEntitiesQualifiedForTwoWaySyncNoCache()
    {
        $cacheKey = 'two_way_sync_entities_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $entities = ['testEntity'];
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $repository->expects($this->once())->method('getEntitiesQualifiedForTwoWaySync')->with($channel)->will(
            $this->returnValue($entities)
        );
        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            ['testEntity']
        );

        $result = $this->mappingProvider->getEntitiesQualifiedForTwoWaySync($channel);

        $this->assertEquals(
            ['testEntity'],
            $result
        );
    }

    public function testGetEntitiesQualifiedForTwoWaySyncCached()
    {
        $cacheKey = 'two_way_sync_entities_1';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(['field' => 'datafield']));
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $result = $this->mappingProvider->getEntitiesQualifiedForTwoWaySync($channel);
        $this->assertEquals(['field' => 'datafield'], $result);
    }

    public function testGetTrackedFieldsConfigNoCache()
    {
        $cacheKey = 'tracked_fields';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $mappings = [];
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
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
        $repository->expects($this->once())->method('findAll')->will($this->returnValue($mappings));
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
        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            $expected
        );

        $result = $this->mappingProvider->getTrackedFieldsConfig();

        $this->assertEquals($expected, $result);
    }

    public function testGetTrackedFieldsConfigModifiedInEvent()
    {
        $cacheKey = 'tracked_fields';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $mappings = [];
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('firstName');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $repository->expects($this->once())->method('findAll')->will($this->returnValue($mappings));

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
        $dispatcher->expects($this->once())->method('hasListeners')->with(MappingTrackedFieldsEvent::NAME)
            ->will($this->returnValue(true));
        $dispatcher->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(MappingTrackedFieldsEvent::class),
            MappingTrackedFieldsEvent::NAME
        )->will($this->returnCallback(function (MappingTrackedFieldsEvent $event, $name) use ($eventData) {
            $event->setFields($eventData);
        }));
        $this->mappingProvider->setDispatcher($dispatcher);

        $this->cache->expects($this->once())->method('save')->with(
            $cacheKey,
            $eventData
        );

        $result = $this->mappingProvider->getTrackedFieldsConfig();

        $this->assertEquals($eventData, $result);
    }

    public function testEntityHasVirutalFieldsMapped()
    {
        $cacheKey = 'tracked_fields';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(false));
        $repository = $this->getRepositoryMock();
        $mappings = [];
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setEntityFields('primaryPhone');
        $mapping->addConfig($mappingConfig);
        $mappings[] = $mapping;
        $repository->expects($this->once())->method('findAll')->will($this->returnValue($mappings));
        $this->virtualFieldsProvider->expects($this->once())->method('isVirtualField')->with(
            'MappingEntityClass',
            'primaryPhone'
        )->will($this->returnValue(true));

        $this->assertTrue($this->mappingProvider->entityHasVirutalFieldsMapped(1, 'MappingEntityClass'));
    }

    public function testGetTrackedFieldsConfigCached()
    {
        $cacheKey = 'tracked_fields';
        $this->cache->expects($this->once())->method('fetch')->with($cacheKey)
            ->will($this->returnValue(['array']));
        $result = $this->mappingProvider->getTrackedFieldsConfig();
        $this->assertEquals(['array'], $result);
    }

    public function testClearCachedValues()
    {
        $mappings = [];
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]);
        $mapping = new DataFieldMapping();
        $mapping->setEntity('MappingEntityClass');
        $mapping->setChannel($channel);
        $mappings[] = $mapping;
        $repository = $this->getRepositoryMock();
        $repository->expects($this->once())->method('findAll')->will($this->returnValue($mappings));
        $this->cache->expects($this->at(0))->method('delete')->with('tracked_fields');
        $this->cache->expects($this->at(1))->method('delete')->with('two_way_sync_entities_1');
        $this->cache->expects($this->at(2))->method('delete')->with('prioritized_1');
        $this->cache->expects($this->at(3))->method('delete')->with('export_MappingEntityClass_1');
        $this->cache->expects($this->at(4))->method('delete')->with('two_way_sync_MappingEntityClass_1');
        $this->mappingProvider->clearCachedValues();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock()
    {
        $repository = $this->createMock(DataFieldMappingRepository::class);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->with(DataFieldMapping::class)
            ->will($this->returnValue($repository));

        return $repository;
    }
}
