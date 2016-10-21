<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

class CampaignDataConverter extends AbstractDataConverter
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
            'fromname' => 'fromName',
            'fromaddress' => 'fromAddress',
            'htmlcontent' => 'htmlContent',
            'plaintextcontent' => 'plainTextContent',
            'replyaction' => 'reply_action:id',
            'replytoaddress' => 'replyToAddress',
            'issplittest' => 'isSplitTest',
            'status' => 'status:id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (is_array($importedRecord['fromaddress'])) {
            $importedRecord['fromaddress'] = $importedRecord['fromaddress']['email'];
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
                'fromname',
                'fromaddress',
                'htmlcontent',
                'plaintextcontent',
                'replyaction',
                'replytoaddress',
                'issplittest',
                'status',
            ];
    }
}
