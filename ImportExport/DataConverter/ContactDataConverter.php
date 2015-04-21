<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class ContactDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [

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
