<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;

class EntityUpdateListener implements OptionalListenerInterface
{
    /** @var bool  */
    protected $enabled = true;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var MappingProvider */
    protected $mappingProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param MappingProvider $mappingProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, MappingProvider $mappingProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * Track changes done on entities fields which are used in mapping configuration. If such field were updated,
     * log changes for further processing
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $updates = $uow->getScheduledEntityUpdates();

        $trackedFieldsConfig = $this->mappingProvider->getTrackedFieldsConfig();
        if ($trackedFieldsConfig) {
            $mappedClasses = array_keys($trackedFieldsConfig);
            foreach ($updates as $entity) {
                $entityClass = $this->doctrineHelper->getEntityClass($entity);
                if (!in_array($entityClass, $mappedClasses)) {
                    continue;
                }
                $trackedFields = array_keys($trackedFieldsConfig[$entityClass]);
                $changedFields = array_keys($uow->getEntityChangeSet($entity));
                $modifiedTrackedFields = array_intersect($changedFields, $trackedFields);
                if ($modifiedTrackedFields) {
                    $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity, false);
                    $metadata = $this->doctrineHelper->getEntityMetadataForClass(ChangedFieldLog::class);
                    foreach ($modifiedTrackedFields as $field) {
                        if (isset($trackedFieldsConfig[$entityClass][$field])) {
                            $fieldConfigs = $trackedFieldsConfig[$entityClass][$field];
                            foreach ($fieldConfigs as $fieldConfig) {
                                $log = new ChangedFieldLog();
                                $log->setChannelId($fieldConfig['channel_id']);
                                $log->setParentEntity($fieldConfig['parent_entity']);
                                $log->setRelatedFieldPath($fieldConfig['field_path']);
                                $log->setRelatedId($entityId);
                                $em->persist($log);
                                $uow->computeChangeSet($metadata, $log);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }
}
