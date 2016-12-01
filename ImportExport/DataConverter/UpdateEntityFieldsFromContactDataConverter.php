<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class UpdateEntityFieldsFromContactDataConverter extends AbstractDataConverter implements
    EntityNameAwareInterface,
    ContextAwareInterface
{
    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var string */
    protected $entityName;

    /** @var ContextInterface */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param MappingProvider $mappingProvider
     */
    public function setMappingProvider(MappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        if (!$this->mappingProvider) {
            throw new RuntimeException('Mapping provider must be set import data fiels values');
        }

        if ($this->context && $this->context->hasOption('channel')) {
            $channelId = $this->context->getOption('channel');
        }
        
        if (!$this->entityName || !isset($channelId)) {
            throw new RuntimeException('Channel and entity name must be set');
        }

        return $this->mappingProvider->getTwoWaySyncFieldsForEntity($this->entityName, $channelId);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $header = array_keys($this->getHeaderConversionRules());
        if (!empty($importedRecord['dataFields'])) {
            $dataFields = [];
            //use only datafields qualified for two way sync
            foreach ($importedRecord['dataFields'] as $key => $value) {
                if (in_array($key, $header)) {
                    $dataFields[$key] = $value;
                }
            }
            unset($importedRecord['dataFields']);
            $importedRecord = array_merge($importedRecord, $dataFields);
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [];
    }
}
