<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

abstract class AbstractDataConverter extends AbstractTableDataConverter
{
    const NULL_VALUE = 'null';

    const XS_BOOLEAN_TRUE = 'true';
    const XS_BOOLEAN_FALSE = 'false';

    /**
     * {@inheritdoc}
     */
    protected function removeEmptyColumns(array $data, $skipNullValues)
    {
        $data = parent::removeEmptyColumns($data, $skipNullValues);

        return array_filter(
            $data,
            function ($item) {
                return $item !== self::NULL_VALUE;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord = parent::convertToImportFormat($importedRecord, $skipNullValues);

        array_walk_recursive(
            $importedRecord,
            function(&$value) {
                if ($value === self::XS_BOOLEAN_FALSE) {
                    $value = false;
                } elseif ($value == self::XS_BOOLEAN_TRUE) {
                    $value = true;
                }
            }
        );

        return $importedRecord;
    }
}
