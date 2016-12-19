<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Entity\Repository\DataFieldRepository;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Provider\CacheProvider;


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

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }
    
    /**
     * @param CacheProvider $cacheProvider
     */
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
            $repository = $this->doctrineHelper->getEntityRepository('OroCRMDotmailerBundle:DataField');
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
