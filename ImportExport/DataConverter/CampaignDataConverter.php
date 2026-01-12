<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

/**
 * Data converter for campaign import/export.
 *
 * Converts campaign data between Dotmailer format and internal representation.
 */
class CampaignDataConverter extends AbstractDataConverter
{
    #[\Override]
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

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (is_array($importedRecord['fromaddress'])) {
            $importedRecord['fromaddress'] = $importedRecord['fromaddress']['email'];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
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
