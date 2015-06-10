<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ExportFaultsReportIterator;

class NotExportedContactDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'email' => 'contact:email',
            ExportFaultsReportIterator::ADDRESS_BOOK_ID => 'addressBook:id'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
