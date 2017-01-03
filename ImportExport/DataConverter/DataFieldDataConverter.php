<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;

class DataFieldDataConverter extends AbstractTableDataConverter
{
    const EMPTY_DEFAULT_VALUE = 'null';

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
        if (isset($importedRecord['defaultvalue'])) {
            if (is_array($importedRecord['defaultvalue'])) {
                $importedRecord['defaultvalue'] = current($importedRecord['defaultvalue']);
            }
            if ($importedRecord['defaultvalue'] === static::EMPTY_DEFAULT_VALUE) {
                $importedRecord['defaultvalue'] = '';
            }
            if ($importedRecord['type'] == DataField::FIELD_TYPE_BOOLEAN && $importedRecord['defaultvalue'] !== '') {
                $importedRecord['defaultvalue'] = ($importedRecord['defaultvalue'] === false) ?
                    DataField::DEFAULT_BOOLEAN_NO : DataField::DEFAULT_BOOLEAN_YES;
            }
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
