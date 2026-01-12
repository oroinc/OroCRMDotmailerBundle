<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Provides common functionality for converting Dotmailer data during import/export.
 *
 * This base class extends the standard table data converter with Dotmailer-specific logic
 * for handling null values. It filters out fields marked with the special 'null' string value
 * to properly handle Dotmailer API responses.
 */
abstract class AbstractDataConverter extends AbstractTableDataConverter
{
    public const NULL_VALUE = 'null';

    #[\Override]
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
}
