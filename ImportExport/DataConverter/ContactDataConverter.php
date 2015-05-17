<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactsIterator;

class ContactDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'id'             => 'originId',
            'status'         => 'status:id',
            'optintype'      => 'opt_in_type:id',
            'emailtype'      => 'email_type:id',
            'FIRSTNAME'      => 'firstName',
            'LASTNAME'       => 'lastName',
            'GENDER'         => 'gender',
            'FULLNAME'       => 'fullName',
            'POSTCODE'       => 'postcode',
            'LASTSUBSCRIBED' => 'lastSubscribedDate',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $allowedKeys = array_fill_keys($this->getBackendHeader(), true);
        $exportedRecord = array_intersect_key($exportedRecord, $allowedKeys);
        $result = parent::convertToExportFormat($exportedRecord, $skipNullValues);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [
            'email',
            'originId',
            'opt_in_type',
            'email_type',
            'firstName',
            'lastName',
            'gender',
            'fullname',
            'postcode',
            ScheduledForExportContactsIterator::ADDRESS_BOOK_KEY
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $header = array_keys($this->getHeaderConversionRules());

        if (!empty($importedRecord['datafields'])) {
            foreach ($importedRecord['datafields'] as $data) {
                if (in_array($data['key'], $header)) {
                    $importedRecord[$data['key']] = is_array($data['value']) ? $data['value'][0] : null;
                }
            }
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
