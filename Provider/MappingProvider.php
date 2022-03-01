<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The provider for different kind mappings used by Dotmailer integration.
 */
class MappingProvider
{
    private DoctrineHelper $doctrineHelper;
    private CacheInterface $cache;
    private VirtualFieldProviderInterface $virtualFieldsProvider;
    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CacheInterface $cache,
        VirtualFieldProviderInterface $virtualFieldsProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
        $this->virtualFieldsProvider = $virtualFieldsProvider;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Prepare mapping array list in format dataField=>entityField, configured for two ways sync from DM
     */
    public function getTwoWaySyncFieldsForEntity(string $entityClass, int $channelId): array
    {
        $cacheKey = $this->getCacheKey(sprintf('two_way_sync_%s_%s', $entityClass, $channelId));
        return $this->cache->get($cacheKey, function () use ($entityClass, $channelId) {
            $mapping = $this->getMappingRepository()->getTwoWaySyncFieldsForEntity($entityClass, $channelId);
            if ($mapping) {
                $idField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
                //add mapping for entityId got from marketing list and entity's id field name
                $mapping['entityId'] = $idField;
            }
            return $mapping;
        });
    }

    /**
     * Prepare mapping array list in format dataField=>entityFields
     */
    public function getExportMappingConfigForEntity(string $entityClass, int $channelId): array
    {
        $cacheKey = $this->getCacheKey(sprintf('export_%s_%s', $entityClass, $channelId));
        return $this->cache->get($cacheKey, function () use ($entityClass, $channelId) {
            return $this->getMappingRepository()->getMappingConfigForEntity($entityClass, $channelId);
        });
    }

    /**
     * Prepare array list of datafields with sync priority set for mapped entity
     * in format [dataField][entityClass] => priority
     */
    public function getDataFieldMappingBySyncPriority(Channel $channel): array
    {
        $channelId = $channel->getId();
        $cacheKey = $this->getCacheKey(sprintf('prioritized_%s', $channelId));
        return $this->cache->get($cacheKey, function () use ($channel) {
            $channelMappings = $this->getMappingRepository()->getMappingBySyncPriority($channel);
            $mappings = [];
            foreach ($channelMappings as $mappingData) {
                $mappings[$mappingData['dataFieldName']][$mappingData['entity']] = $mappingData['syncPriority'];
            }
            return $mappings;
        });
    }

    public function getEntitiesQualifiedForTwoWaySync(Channel $channel): array
    {
        $channelId = $channel->getId();
        $cacheKey = $this->getCacheKey(sprintf('two_way_sync_entities_%s', $channelId));
        return $this->cache->get($cacheKey, function () use ($channel) {
            return $this->getMappingRepository()->getEntitiesQualifiedForTwoWaySync($channel);
        });
    }

    /**
     * Returns array of all entity fields configured for sync with Dotmailer
     *
     * [
     *   entityClass => [
     *      fieldName => [
     *          [
     *              channel_id - mapping's channel id
     *              parent_entity - entity used in the mapping
     *              field_path - full tracked field path used in the mapping
     *          ],
     *          ...
     *      ],
     *      anotherFieldName => [
     *          ...
     *      ]
     *   ],
     *   anotherEntityClass => [
     *      ...
     *   ]
     * ]
     */
    public function getTrackedFieldsConfig(): array
    {
        $cacheKey = 'tracked_fields';
        return $this->cache->get($cacheKey, function () {
            $trackedFields = [];
            $mappings = $this->getMappingRepository()->findAll();
            foreach ($mappings as $mapping) {
                $parentEntity = $mapping->getEntity();
                $channelId = $mapping->getChannel()->getId();
                $joinIdentifierHelper = new JoinIdentifierHelper($parentEntity);
                $fields = [];
                foreach ($mapping->getConfigs() as $mappingConfig) {
                    $fields = array_merge($fields, explode(',', $mappingConfig->getEntityFields()));
                }
                $fields = array_unique($fields);
                foreach ($fields as $field) {
                    $class = $joinIdentifierHelper->getEntityClassName($field);
                    $fieldName = $joinIdentifierHelper->getFieldName($field);
                    if ($this->virtualFieldsProvider->isVirtualField($class, $fieldName)) {
                        $trackedFields[$channelId][$parentEntity]['hasMappedVirtualFields'] = true;
                    }
                    if (!isset($trackedFields[$class][$fieldName])) {
                        $trackedFields[$class][$fieldName] = [];
                    }
                    $trackedFields[$class][$fieldName][] = [
                        'channel_id'    => $channelId,
                        'parent_entity' => $parentEntity,
                        'field_path'    => $field
                    ];
                }
            }
            if ($this->dispatcher && $this->dispatcher->hasListeners(MappingTrackedFieldsEvent::NAME)) {
                $event = new MappingTrackedFieldsEvent($trackedFields);
                $this->dispatcher->dispatch($event, MappingTrackedFieldsEvent::NAME);
                $trackedFields = $event->getFields();
            }
            return $trackedFields;
        });
    }

    public function entityHasVirutalFieldsMapped(int $channelId, string $entityClass): bool
    {
        $trackedFields = $this->getTrackedFieldsConfig();

        return !empty($trackedFields[$channelId][$entityClass]['hasMappedVirtualFields']);
    }

    /**
     * Clear all mapping related caches. Need to delete each key seprately, deleteAll cannot be used
     * because message consumers may still use old caches after this
     */
    public function clearCachedValues()
    {
        $cacheKeys = [];
        $cacheKeys[] = 'tracked_fields';
        $mappings = $this->getMappingRepository()->findAll();
        /** @var DataFieldMapping $mapping */
        foreach ($mappings as $mapping) {
            $entityClass = $mapping->getEntity();
            $channelId = $mapping->getChannel()->getId();
            $cacheKeys[] = $this->getCacheKey(sprintf('two_way_sync_entities_%s', $channelId));
            $cacheKeys[] = $this->getCacheKey(sprintf('prioritized_%s', $channelId));
            $cacheKeys[] = $this->getCacheKey(sprintf('export_%s_%s', $entityClass, $channelId));
            $cacheKeys[] = $this->getCacheKey(sprintf('two_way_sync_%s_%s', $entityClass, $channelId));
        }
        foreach ($cacheKeys as $cacheKey) {
            $this->cache->delete($cacheKey);
        }
    }

    protected function getMappingRepository(): DataFieldMappingRepository
    {
        return $this->doctrineHelper->getEntityRepository(DataFieldMapping::class);
    }

    private function getCacheKey(string $key): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($key);
    }
}
