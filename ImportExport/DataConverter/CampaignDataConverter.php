<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class CampaignDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'id' => 'originId',
            'name' => 'name',
            'subject' => 'subject',
            'fromName' => 'fromName',
            'fromAddress' => 'fromAddress',
            'htmlContent' => 'htmlContent',
            'plainTextContent' => 'plainTextContent',
            'replyAction' => 'replyAction',
            'replyToAddress' => 'replyToAddress',
            'isSplitTest' => 'isSplitTest',
            'status' => 'status',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (is_array($importedRecord['fromAddress'])) {
            $importedRecord['fromAddress'] = $importedRecord['fromAddress']['email'];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return
            [
                'id',
                'name',
                'subject',
                'fromName',
                'fromAddress',
                'htmlContent',
                'plainTextContent',
                'replyAction',
                'replyToAddress',
                'isSplitTest',
                'status',
            ];
    }
}
