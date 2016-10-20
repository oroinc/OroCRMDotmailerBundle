<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

abstract class AbstractDataConverter extends AbstractTableDataConverter
{
    const NULL_VALUE = 'null';

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
}
