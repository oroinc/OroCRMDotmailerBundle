<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Actualize DM mapping on entity changes
 */
class MappingUpdateListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    const MAPPING_CONFIGS_FIELD = 'configs';

    /** @var array  */
    protected $entityUpdateScheduled = [];

    /** @var array  */
    protected $entityFieldUpdateScheduled = [];

    protected $rebuildMappingCache = false;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var MappingProvider */
    protected $mappingProvider;

    public function __construct(DoctrineHelper $doctrineHelper, MappingProvider $mappingProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * Process changed done on mapping configurations and update corresponding flags for address book contacts
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $this->entityUpdateScheduled = [];
        $this->entityFieldUpdateScheduled = [];
        $hasNewMappings = $this->checkInsertions($uow);
        $hasUpdatedMappings = $this->checkUpdates($uow);
        $hasRemovedMappings = $this->checkRemoved($uow);
        if ($hasNewMappings || $hasUpdatedMappings || $hasRemovedMappings) {
            $this->rebuildMappingCache = true;
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->rebuildMappingCache) {
            //if any updates were done to mappings configurations, clear mapping related cache
            $this->mappingProvider->clearCachedValues();
            //ensure tracked fields config is rebuilt and saved to cache
            $this->mappingProvider->getTrackedFieldsConfig();
            $this->rebuildMappingCache = false;
        }
    }

    /**
     * @param UnitOfWork $uow
     * @return bool
     */
    protected function checkInsertions(UnitOfWork $uow)
    {
        $insertions = $uow->getScheduledEntityInsertions();
        $hasNewMappings = false;
        foreach ($insertions as $entity) {
            if ($this->isMappingRelatedEntity($entity)) {
                $hasNewMappings = true;
            }
            if (!$this->isDataFieldMappingConfig($entity)) {
                continue;
            }
            /** @var DataFieldMappingConfig $entity */
            $mapping = $entity->getMapping();
            /**
             * If new mapping was added, we need to export all values from related entities
             */
            $this->updateEntityUpdateFlag($mapping);
            if ($entity->isIsTwoWaySync()) {
                /**
                 * If at least one datafield is configured for two way sync,
                 * we need to import datafield values to related entities
                 */
                $this->updateScheduledForEntityFieldUpdateFlag($mapping);
            }
        }

        return $hasNewMappings;
    }

    /**
     * @param UnitOfWork $uow
     * @return bool
     */
    protected function checkUpdates(UnitOfWork $uow)
    {
        $updates = $uow->getScheduledEntityUpdates();
        $hasUpdatedMappings = false;
        foreach ($updates as $entity) {
            if ($this->isMappingRelatedEntity($entity)) {
                $hasUpdatedMappings = true;
            }
            if (!$this->isDataFieldMappingConfig($entity)) {
                continue;
            }
            /** @var DataFieldMappingConfig $entity */
            $mapping = $entity->getMapping();
            $changeSet = $uow->getEntityChangeSet($entity);
            if (isset($changeSet['entityFields']) || isset($changeSet['dataField'])) {
                /**
                 * If we have at least one changed mapping for a datafield, we need to re-export all values
                 * from related entities
                 */
                $this->updateEntityUpdateFlag($mapping);
            }
            if (isset($changeSet['isTwoWaySync']) && $changeSet['isTwoWaySync'][1]) {
                /**
                 * If we have new datafields for two way sync, we need to re-import datafield values to related entities
                 */
                $this->updateScheduledForEntityFieldUpdateFlag($mapping);
            }
        }

        return $hasUpdatedMappings;
    }

    /**
     * @param UnitOfWork $uow
     * @return bool
     */
    protected function checkRemoved(UnitOfWork $uow)
    {
        $hasRemovedMappings = false;
        $removed = $uow->getScheduledEntityDeletions();
        foreach ($removed as $entity) {
            if ($this->isMappingRelatedEntity($entity)) {
                $hasRemovedMappings = true;
                break;
            }
        }

        return $hasRemovedMappings;
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isDataFieldMappingConfig($entity)
    {
        return $entity instanceof DataFieldMappingConfig;
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isMappingRelatedEntity($entity)
    {
        return ($entity instanceof DataFieldMapping) || $this->isDataFieldMappingConfig($entity);
    }

    /**
     * @param DataFieldMapping $mapping
     */
    protected function updateEntityUpdateFlag($mapping)
    {
        if (empty($this->entityUpdateScheduled[$mapping->getEntity()])) {
            /** @var AddressBookContactRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroDotmailerBundle:AddressBookContact');
            $repository->bulkUpdateEntityUpdatedFlag($mapping->getEntity(), $mapping->getChannel());
            $this->entityUpdateScheduled[$mapping->getEntity()] = true;
        }
    }

    /**
     * @param DataFieldMapping $mapping
     */
    protected function updateScheduledForEntityFieldUpdateFlag($mapping)
    {
        if (empty($this->entityFieldUpdateScheduled[$mapping->getEntity()])) {
            /** @var AddressBookContactRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepositoryForClass('OroDotmailerBundle:AddressBookContact');
            $repository->bulkUpdateScheduledForEntityFieldUpdateFlag($mapping->getEntity(), $mapping->getChannel());
            $this->entityFieldUpdateScheduled[$mapping->getEntity()] = true;
        }
    }
}
