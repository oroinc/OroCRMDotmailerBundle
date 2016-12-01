<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldMappingRepository;

class MappingProvider
{
    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var  array */
    protected $mappings = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
        if (!isset($this->mappings[$entityClass][$channelId])) {
            /** @var DataFieldMappingRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(DataFieldMapping::class);
            $mapping = $repository->getTwoWaySyncFieldsForEntity($entityClass, $channelId);
            if ($mapping) {
                $mapping = array_column($mapping, 'entityFieldName', 'dataFieldName');
                $idField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
                //add mapping for entityId got from marketing list and entity's id field name
                $mapping['entityId'] = $idField;
            }
            $this->mappings[$entityClass][$channelId] = $mapping;
        }

        return $this->mappings[$entityClass][$channelId];
    }
}
