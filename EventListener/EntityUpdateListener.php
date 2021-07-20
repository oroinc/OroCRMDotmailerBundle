<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ChangedFieldLogRepository;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Tracks changes that done on fields of entities which are used in mapping configuration
 */
class EntityUpdateListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var array */
    protected $logs;

    public function __construct(DoctrineHelper $doctrineHelper, MappingProvider $mappingProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * Track changes done on entities fields which are used in mapping configuration. If such field were updated,
     * log changes for further processing
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entities = array_merge($uow->getScheduledEntityUpdates(), $uow->getScheduledEntityInsertions());

        $trackedFieldsConfig = $this->mappingProvider->getTrackedFieldsConfig();
        if ($trackedFieldsConfig) {
            $mappedClasses = array_keys($trackedFieldsConfig);
            foreach ($entities as $entity) {
                $entityClass = $this->doctrineHelper->getEntityClass($entity);
                if (!in_array($entityClass, $mappedClasses, true)) {
                    continue;
                }
                $isInsertion = $uow->isScheduledForInsert($entity);
                $trackedFields = array_keys($trackedFieldsConfig[$entityClass]);
                $changedFields = array_keys($uow->getEntityChangeSet($entity));
                $modifiedTrackedFields = array_intersect($changedFields, $trackedFields);
                if ($modifiedTrackedFields) {
                    $metadata = $this->doctrineHelper->getEntityMetadataForClass(ChangedFieldLog::class);
                    foreach ($modifiedTrackedFields as $field) {
                        if (isset($trackedFieldsConfig[$entityClass][$field])) {
                            $fieldConfigs = $trackedFieldsConfig[$entityClass][$field];
                            foreach ($fieldConfigs as $fieldConfig) {
                                $log = $this->createLog($fieldConfig, $entity, $isInsertion);

                                $em->persist($log);
                                $uow->computeChangeSet($metadata, $log);

                                $this->schedule($log, $entity, $isInsertion);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $fieldConfig
     * @param object $entity
     * @param bool $isInsertion
     * @return ChangedFieldLog
     */
    private function createLog(array $fieldConfig, $entity, $isInsertion)
    {
        $log = new ChangedFieldLog();
        $log->setChannelId($fieldConfig['channel_id']);
        $log->setParentEntity($fieldConfig['parent_entity']);
        $log->setRelatedFieldPath($fieldConfig['field_path']);

        if (!$isInsertion) {
            $log->setRelatedId(
                $this->doctrineHelper->getSingleEntityIdentifier($entity, false)
            );
        }

        return $log;
    }

    /**
     * @param ChangedFieldLog $log
     * @param object $entity
     * @param bool $isInsertion
     */
    private function schedule(ChangedFieldLog $log, $entity, $isInsertion)
    {
        if ($isInsertion) {
            $this->logs[] = ['entity' => $entity, 'log' => $log];
        }
    }

    /**
     * Update log entries with inserted related entity ids
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled || empty($this->logs)) {
            return;
        }

        /** @var ChangedFieldLogRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(ChangedFieldLog::class);

        foreach ($this->logs as $log) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($log['entity'], false);
            /** @var ChangedFieldLog $logEntity */
            $logEntity = $log['log'];
            $repository->addEntityIdToLog($entityId, $logEntity->getId());
        }

        $this->logs = [];
    }
}
