<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\Common\Cache\CacheProvider as DoctrineCacheProvider;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for different kind mappings used by Dotmailer integration.
 */
class MappingProvider
{
    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var DoctrineCacheProvider */
    protected $cache;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldsProvider;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        DoctrineCacheProvider $cache,
        VirtualFieldProviderInterface $virtualFieldsProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
        $this->virtualFieldsProvider = $virtualFieldsProvider;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Prepare mapping array list in format dataField=>entityField, configured for two way sync from DM
     *
     * @param string $entityClass
     * @param int $channelId
     * @return array
     */
    public function getTwoWaySyncFieldsForEntity($entityClass, $channelId)
    {
        $cacheKey = sprintf('two_way_sync_%s_%s', $entityClass, $channelId);
        $twoWayMappings = $this->cache->fetch($cacheKey);
        if (false === $twoWayMappings) {
            $mapping = $this->getMappingRepository()->getTwoWaySyncFieldsForEntity($entityClass, $channelId);
            if ($mapping) {
                $idField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
                //add mapping for entityId got from marketing list and entity's id field name
                $mapping['entityId'] = $idField;
            }
            $twoWayMappings = $mapping;
            $this->cache->save($cacheKey, $twoWayMappings);
        }

        return $twoWayMappings;
    }

    /**
     * Prepare mapping array list in format dataField=>entityFields
     *
     * @param string $entityClass
     * @param int $channelId
     * @return mixed
     */
    public function getExportMappingConfigForEntity($entityClass, $channelId)
    {
        $cacheKey = sprintf('export_%s_%s', $entityClass, $channelId);
        $exportMappings = $this->cache->fetch($cacheKey);
        if (false === $exportMappings) {
            $exportMappings = $this->getMappingRepository()->getMappingConfigForEntity($entityClass, $channelId);
            $this->cache->save($cacheKey, $exportMappings);
        }

        return $exportMappings;
    }

    /**
     * Prepare array list of datafields with sync priority set for mapped entity
     * in format [dataField][entityClass] => priority
     *
     * @param Channel $channel
     * @return array
     */
    public function getDataFieldMappingBySyncPriority(Channel $channel)
    {
        $channelId = $channel->getId();
        $cacheKey = sprintf('prioritized_%s', $channelId);
        $prioritizedMappings = $this->cache->fetch($cacheKey);
        if (false === $prioritizedMappings) {
            $channelMappings = $this->getMappingRepository()->getMappingBySyncPriority($channel);
            $mappings = [];
            foreach ($channelMappings as $mappingData) {
                $mappings[$mappingData['dataFieldName']][$mappingData['entity']] = $mappingData['syncPriority'];
            }
            $prioritizedMappings = $mappings;
            $this->cache->save($cacheKey, $prioritizedMappings);
        }

        return $prioritizedMappings;
    }

    /**
     * @param Channel $channel
     * @return array
     */
    public function getEntitiesQualifiedForTwoWaySync(Channel $channel)
    {
        $channelId = $channel->getId();
        $cacheKey = sprintf('two_way_sync_entities_%s', $channelId);
        $entities = $this->cache->fetch($cacheKey);
        if (false === $entities) {
            $entities = $this->getMappingRepository()->getEntitiesQualifiedForTwoWaySync($channel);
            $this->cache->save($cacheKey, $entities);
        }

        return $entities;
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
     *
     * @return array
     */
    public function getTrackedFieldsConfig()
    {
        $cacheKey = 'tracked_fields';
        $trackedFields = $this->cache->fetch($cacheKey);
        if (false === $trackedFields) {
            $trackedFields = [];
            $mappings = $this->getMappingRepository()->findAll();
            /** @var DataFieldMapping $mapping */
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
            $this->cache->save($cacheKey, $trackedFields);
        }

        return $trackedFields;
    }

    /**
     * @param int $channelId
     * @param string $entityClass
     * @return bool
     */
    public function entityHasVirutalFieldsMapped($channelId, $entityClass)
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
            $cacheKeys[] = sprintf('two_way_sync_entities_%s', $channelId);
            $cacheKeys[] = sprintf('prioritized_%s', $channelId);
            $cacheKeys[] = sprintf('export_%s_%s', $entityClass, $channelId);
            $cacheKeys[] = sprintf('two_way_sync_%s_%s', $entityClass, $channelId);
        }
        foreach ($cacheKeys as $cacheKey) {
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * @return DataFieldMappingRepository
     */
    protected function getMappingRepository()
    {
        return $this->doctrineHelper->getEntityRepository(DataFieldMapping::class);
    }
}
