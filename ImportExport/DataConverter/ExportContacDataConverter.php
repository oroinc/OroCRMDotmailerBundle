<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

class ExportContacDataConverter implements DataConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $importedRecord, $skipNullValues = true)
    {
        $convertedData = [
            'Email' => $importedRecord[MarketingListItemsQueryBuilderProvider::CONTACT_EMAIL_FIELD],
            'Firstname' => $importedRecord[MarketingListItemsQueryBuilderProvider::CONTACT_FIRS_NAME_FIELD],
            'Lastname' => $importedRecord[MarketingListItemsQueryBuilderProvider::CONTACT_LAST_NAME_FIELD],
        ];
        //todo: trigger an event for make possible to change converted data for customizations
        return $convertedData;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        throw new \Exception('Denormalization is not implemented!');
    }
}
