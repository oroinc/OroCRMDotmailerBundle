<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\Repository\DataFieldRepository;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Prepares Contact records data to be saved to orocrm_dm_contact table before export these entities to dotmailer
 */
class ContactSyncDataConverter extends AbstractDataConverter implements ContextAwareInterface
{
    const EMAIL_FIELD = 'email';
    const OPT_IN_TYPE_FIELD = 'optInType';
    const EMAIL_TYPE_FIELD = 'emailType';
    const DATAFIELDS_FIELD = 'dataFields';

    const CACHED_DATAFIELDS = 'cachedDatafields';

    /** @var ContextInterface */
    protected $context;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            self::EMAIL_FIELD       => 'email',
            self::OPT_IN_TYPE_FIELD => 'opt_in_type:id',
            self::EMAIL_TYPE_FIELD  => 'email_type:id',
            self::DATAFIELDS_FIELD => 'dataFields'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $dataFields = $this->getChannelDataFields();
        $dataFieldValues = [];
        if ($dataFields) {
            foreach ($dataFields as $name => $type) {
                if (isset($importedRecord[$name])) {
                    $dataFieldValues[$name] = $this->prepareFieldForExport($importedRecord[$name], $type);
                    unset($importedRecord[$name]);
                }
            }
        }
        $importedRecord[self::DATAFIELDS_FIELD] = $dataFieldValues;

        /**
         * Due to implicit Dotmailer API logic which causes all emails passed always turned to lowercase
         * we need this workaround to have all our `orocrm_dm_contact.email` records prepared to export (null originId)
         * to be converted to lowercase before saving as well. So this will make possible syncing emails which
         * have different letter cases.
         */
        if (isset($importedRecord[self::EMAIL_FIELD])) {
            $importedRecord[self::EMAIL_FIELD] = strtolower($importedRecord[self::EMAIL_FIELD]);
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    protected function prepareFieldForExport($value, $type)
    {
        switch ($type) {
            case DataField::FIELD_TYPE_DATE:
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d\TH:i:s');
                }
                break;
        }

        return $value;
    }

    /**
     * @return array
     */
    protected function getChannelDataFields()
    {
        if ($this->context && $this->context->hasOption('channel')) {
            $channelId = $this->context->getOption('channel');
        }
        if (empty($channelId)) {
            throw new RuntimeException('Channel must be set');
        }
        $dataFields = $this->cacheProvider->getCachedItem(self::CACHED_DATAFIELDS, $channelId);
        if (!$dataFields) {
            /** @var DataFieldRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository('OroDotmailerBundle:DataField');
            $fields = $repository->findBy(['channel' => $channelId]);
            $dataFields = [];
            /** @var DataField $dataField */
            foreach ($fields as $dataField) {
                $dataFields[$dataField->getName()] = $dataField->getType()->getId();
            }
            $this->cacheProvider->setCachedItem(self::CACHED_DATAFIELDS, $channelId, $dataFields);
        }

        return $dataFields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [];
    }
}
