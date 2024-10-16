<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class UnsubscribedFromAccountContactDataConverter extends AbstractTableDataConverter
{
    const CONTACT_EMAIL = 'email';

    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            self::CONTACT_EMAIL => 'email',
            'dateremoved'       => 'unsubscribedDate',
            'reason'            => 'status:id',
        ];
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (is_array($importedRecord['suppressedcontact'])) {
            $importedRecord[self::CONTACT_EMAIL] = $importedRecord['suppressedcontact']['email'];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
