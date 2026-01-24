<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ExportFaultsReportIterator;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Data converter for non-exported contact import/export.
 *
 * Converts non-exported contact data between Dotmailer format and internal representation.
 */
class NotExportedContactDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'email' => 'contact:email',
            ExportFaultsReportIterator::ADDRESS_BOOK_ID => 'addressBook:id'
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
