<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;

class AddDefaultMappingListener extends AbstractImportExportListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

    /**
     * @var MappingUpdateListener
     */
    protected $mappingListener;

    /** @var MappingProvider */
    protected $mappingProvider;

    public function __construct(
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        EntityProvider $entityProvider,
        DefaultOwnerHelper $ownerHelper,
        MappingUpdateListener $mappingListener,
        MappingProvider $mappingProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityProvider = $entityProvider;
        $this->ownerHelper = $ownerHelper;
        $this->mappingListener = $mappingListener;
        $this->mappingProvider = $mappingProvider;
        parent::__construct($registry);
    }

    public function afterSyncFinished(SyncEvent $syncEvent)
    {
        if (!$this->isApplicable($syncEvent, DataFieldConnector::IMPORT_JOB)) {
            return;
        }

        $channel = $this->getChannel($syncEvent->getConfiguration());

        $manager = $this->registry->getManager();
        $mappingConfiguration = $this->getDefaultMappingConfiguration();
        $entities = $this->entityProvider->getEntities();
        $dataFields = $this->getMappingDataFields($channel);
        /*
         * disable mapping listener while default mappings are created to avoid re-export of all existing
         * connected entities
         */
        $this->mappingListener->setEnabled(false);
        foreach ($entities as $entity) {
            $entity = $entity['name'];
            if ($this->mappingExists($channel, $entity)) {
                continue;
            }
            $mapping = $this->buildDataFieldMapping($channel, $entity);
            $metadata = $this->doctrineHelper->getEntityMetadata($entity);
            $entitFields = $metadata->getFieldNames();
            foreach ($mappingConfiguration as $fields => $dataField) {
                if (isset($dataFields[$dataField]) && $this->isAllMappingFieldsFound($fields, $entitFields)) {
                    $mappingConfig = $this->buildDataFieldMappingConfig($fields, $dataFields[$dataField]);
                    $mapping->addConfig($mappingConfig);
                }
            }
            //save mapping in case we added at list one mapping configuration
            if ($mapping->getConfigs()->count()) {
                $manager->persist($mapping);
                $manager->flush();
            }
        }
        $this->mappingProvider->clearCachedValues();
        $this->mappingListener->setEnabled(true);
    }

    /**
     * @param Channel $channel
     * @param string $entity
     *
     * @return bool
     */
    protected function mappingExists(Channel $channel, $entity)
    {
        $mapping = $this->registry->getRepository('OroDotmailerBundle:DataFieldMapping')->findOneBy(
            [
                'channel' => $channel,
                'entity'  => $entity
            ]
        );

        return ($mapping === null) ? false : true;
    }

    /**
     * @param Channel $channel
     * @param string $entity
     *
     * @return DataFieldMapping
     */
    protected function buildDataFieldMapping(Channel $channel, $entity)
    {
        $mapping = new DataFieldMapping();
        $mapping->setChannel($channel);
        $mapping->setEntity($entity);
        $priorityList = $this->getDefaultEntitiesPriorityList();
        $mapping->setSyncPriority(!empty($priorityList[$entity]) ? $priorityList[$entity] : 0);
        $this->ownerHelper->populateChannelOwner($mapping, $channel);

        return $mapping;
    }

    /**
     * @param $fields
     * @param $dataField
     *
     * @return DataFieldMappingConfig
     */
    protected function buildDataFieldMappingConfig($fields, $dataField)
    {
        $mappingConfig = new DataFieldMappingConfig();
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields($fields);

        return $mappingConfig;
    }

    /**
     * Check that all mapped fields exist in the entity
     *
     * @param array $fields
     * @param array $entityFields
     *
     * @return bool
     */
    protected function isAllMappingFieldsFound($fields, $entityFields)
    {
        $fields = explode(',', $fields);
        $allFieldsFound = !array_diff($fields, $entityFields);

        return $allFieldsFound;
    }

    /**
     * @return array
     */
    protected function getDefaultMappingConfiguration()
    {
        $mapping = [
            'firstName'          => 'FIRSTNAME',
            'lastName'           => 'LASTNAME',
            'firstName,lastName' => 'FULLNAME',
        ];

        return $mapping;
    }

    /**
     * Set priority for Lead and Contact entities if they are available
     *
     * @return array
     */
    protected function getDefaultEntitiesPriorityList()
    {
        $priorityList = [
            'Oro\Bundle\ContactBundle\Entity\Contact' => 20,
            'Oro\Bundle\SalesBundle\Entity\Lead' => 10,
        ];

        return $priorityList;
    }

    /**
     * Default mapping should be created only after the first data fields synchronization
     *
     * @param SyncEvent $syncEvent
     * @param string $job
     *
     * @return bool
     */
    protected function isApplicable(SyncEvent $syncEvent, $job)
    {
        $isApplicable = parent::isApplicable($syncEvent, $job)
            && $this->isFirstDataFieldSyncJob($this->getChannel($syncEvent->getConfiguration()));

        return $isApplicable;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    protected function isFirstDataFieldSyncJob(Channel $channel)
    {
        $queryBuilder = $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getConnectorStatusesQueryBuilder($channel, DataFieldConnector::TYPE, Status::STATUS_COMPLETED);
        $queryBuilder->select('COUNT(status.id) as statusCount');
        $queryBuilder->resetDQLPart('orderBy');
        $isFirst = $queryBuilder->getQuery()->getSingleScalarResult() == 0;

        return $isFirst;
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    protected function getMappingDataFields(Channel $channel)
    {
        $mapping = $this->getDefaultMappingConfiguration();
        $names = array_values($mapping);
        $fields = $this->registry->getRepository('OroDotmailerBundle:DataField')
            ->getChannelDataFieldByNames($names, $channel);

        return $fields;
    }

    /**
    * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        return array(
            SyncEvent::SYNC_AFTER => 'afterSyncFinished'
        );
    }
}
