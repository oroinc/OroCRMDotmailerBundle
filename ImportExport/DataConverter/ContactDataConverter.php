<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Prepares dotmailer contact data for import and export
 */
class ContactDataConverter extends AbstractDataConverter implements ContextAwareInterface
{
    const ADDRESS_BOOK_CONTACT_ID = 'addressBookContactId';

    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var ContextInterface */
    protected $context;

    public function setMappingProvider(MappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'id'             => 'originId',
            'status'         => 'status:id',
            'optintype'      => 'opt_in_type:id',
            'emailtype'      => 'email_type:id',
            'LASTSUBSCRIBED' => 'lastSubscribedDate',
            'datafields'     => 'dataFields'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $backendHeader = [
            static::ADDRESS_BOOK_CONTACT_ID,
            'email',
            'originId',
            'optInType',
            'emailType',
            ScheduledForExportContactIterator::ADDRESS_BOOK_KEY
        ];

        return $backendHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (!empty($importedRecord['datafields'])) {
            $dataFields = [];
            foreach ((array)$importedRecord['datafields'] as $data) {
                $dataFields[$data['key']] = is_array($data['value']) ? $data['value'][0] : null;
            }
            $importedRecord['datafields'] = $dataFields;
            if (isset($dataFields['LASTSUBSCRIBED'])) {
                //stored separately as flat field
                $importedRecord['LASTSUBSCRIBED'] = $dataFields['LASTSUBSCRIBED'];
            }
        } else {
            $importedRecord['datafields'] = [];
        }

        /**
         * Due to implicit Dotmailer API logic which causes all emails passed always turned to lowercase
         * we need to cast emails to lowercase to have all our `orocrm_dm_contact.email` records prepared to export
         * (null originId) to be converted to lowercase before saving as well. So this will make possible syncing
         * emails which have different letter cases.
         */
        if (isset($importedRecord[ContactSyncDataConverter::EMAIL_FIELD])) {
            $importedRecord[ContactSyncDataConverter::EMAIL_FIELD] =
                strtolower($importedRecord[ContactSyncDataConverter::EMAIL_FIELD]);
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $entityClass = !empty($exportedRecord['entityClass']) ? $exportedRecord['entityClass'] : false;
        $this->initEntityBackendHeader($entityClass);
        $dataFields = $this->getMappedDataFields($entityClass);
        if (isset($exportedRecord['dataFields'])) {
            $contactDataFields = $exportedRecord['dataFields'];
            foreach ($dataFields as $dataField) {
                $exportedRecord[$dataField] = isset($contactDataFields[$dataField]) ?
                    $contactDataFields[$dataField] : null;
            }
        }
        unset($exportedRecord['dataFields']);
        unset($exportedRecord['entityClass']);

        return parent::convertToExportFormat($exportedRecord, $skipNullValues);
    }

    /**
     * Dynamically update backend header based on entity class and it's mapping
     *
     * @param string $entityClass
     */
    protected function initEntityBackendHeader($entityClass)
    {
        $backendHeader = $this->getBackendHeader();
        if ($entityClass) {
            $dataFields = $this->getMappedDataFields($entityClass);
            $backendHeader = array_merge($backendHeader, $dataFields);
        }

        $this->backendHeader = $backendHeader;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getMappedDataFields($entityClass)
    {
        if (!$this->mappingProvider) {
            throw new RuntimeException('Mapping provider must be set import data fiels values');
        }

        if ($this->context && $this->context->hasOption('channel')) {
            $channelId = $this->context->getOption('channel');
        }

        if (!isset($channelId)) {
            throw new RuntimeException('Channel and entity name must be set');
        }
        $mapping = $this->mappingProvider->getExportMappingConfigForEntity($entityClass, $channelId);
        $dataFields = array_keys($mapping);

        return $dataFields;
    }
}
