<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\EmailProvider;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;

class UpdateEntityFieldsFromContactDataConverter extends AbstractDataConverter implements
    EntityNameAwareInterface,
    ContextAwareInterface
{
    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var EmailProvider */
    protected $emailProvider;

    /** @var string */
    protected $entityName;

    /** @var ContextInterface */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setEntityName(string $entityName): void
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

    public function setMappingProvider(MappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    public function setEmailProvider(EmailProvider $emailProvider)
    {
        $this->emailProvider = $emailProvider;
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
        $mapping = $this->mappingProvider->getTwoWaySyncFieldsForEntity($this->entityName, $channelId);

        return $mapping;
    }

    /**
     * {@inheritdoc}
     */
    protected function receiveBackendToFrontendHeader()
    {
        /**
         * Do not cache header because it's generated dynamically based on the mapping
         */
        $header = $this->receiveBackendHeader();
        $this->backendToFrontendHeader = $this->convertHeaderToFrontend($header);

        return $this->backendToFrontendHeader;
    }

    /**
     * {@inheritdoc}
     */
    protected function receiveHeaderConversionRules()
    {
        /**
         * Do not cache header because it's generated dynamically based on the mapping
         */
        $this->headerConversionRules = $this->getHeaderConversionRules();

        return $this->headerConversionRules;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $header = array_keys($this->getHeaderConversionRules());
        if (!empty($importedRecord['dataFields'])) {
            $dataFields = [];
            foreach ($importedRecord['dataFields'] as $key => $value) {
                //use only datafields qualified for two way sync
                if (in_array($key, $header)) {
                    $dataFields[$key] = $value;
                }
            }
            unset($importedRecord['dataFields']);
            $importedRecord = array_merge($importedRecord, $dataFields);
        }

        //adding email only for non existing entities
        if (empty($importedRecord['entityId'])) {
            $emailField = $this->emailProvider->getEntityEmailField($this->entityName);
            if (is_array($emailField)) {
                $importedRecord[$emailField['entityEmailField']] = [
                    [$emailField['emailField'] => $importedRecord['email']]
                ];
            } elseif ($emailField) {
                $importedRecord[$emailField] = $importedRecord['email'];
            }
        } else {
            unset($importedRecord['email']);
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
