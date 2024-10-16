<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class AddressBookDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'id' => 'originId',
            'name' => 'name',
            'visibility' => 'visibility:id',
            'contacts' => 'contactCount',
        ];
    }

    /**
     * Get maximum backend header for current entity
     *
     * @return array
     */
    #[\Override]
    protected function getBackendHeader()
    {
        return ['id', 'name', 'visibility', 'contacts'];
    }
}
