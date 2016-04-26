<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

class ContactDataConverter extends AbstractDataConverter
{
    const ADDRESS_BOOK_CONTACT_ID = 'addressBookContactId';

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
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [
            static::ADDRESS_BOOK_CONTACT_ID,
            'email',
            'originId',
            'optInType',
            'emailType',
            'firstName',
            'lastName',
            'gender',
            'fullName',
            'postcode',
            ScheduledForExportContactIterator::ADDRESS_BOOK_KEY
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $header = array_keys($this->getHeaderConversionRules());

        if (!empty($importedRecord['datafields'])) {
            foreach ((array)$importedRecord['datafields'] as $data) {
                if (in_array($data['key'], $header)) {
                    $importedRecord[$data['key']] = is_array($data['value']) ? $data['value'][0] : null;
                }
            }
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
