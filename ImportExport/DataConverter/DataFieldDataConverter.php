<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class DataFieldDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'name' => 'name',
            'visibility' => 'visibility:id',
            'type' => 'type:id',
            'defaultvalue' => 'defaultValue',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (is_array($importedRecord['defaultvalue'])) {
            $importedRecord['defaultvalue'] = current($importedRecord['defaultvalue']);
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * Get maximum backend header for current entity
     *
     * @return array
     */
    protected function getBackendHeader()
    {
        return ['name', 'visibility', 'type', 'defaultvalue'];
    }
}
