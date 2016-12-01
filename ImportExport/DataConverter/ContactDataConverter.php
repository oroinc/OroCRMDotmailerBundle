<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\ScheduledForExportContactIterator;

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
            'datafields'     => 'dataFields'
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
        if (!empty($importedRecord['datafields'])) {
            $dataFields = [];
            foreach ((array)$importedRecord['datafields'] as $data) {
                $dataFields[$data['key']] = is_array($data['value']) ? $data['value'][0] : null;
            }
            $importedRecord['datafields'] = $dataFields;
            if (isset($dataFields['LASTSUBSCRIBED'])) {
                //stored separately as flat field
                $importedRecord['LASTSUBSCRIBED'] = $dataFields['LASTSUBSCRIBED'];
            }
        } else {
            $importedRecord['datafields'] = [];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
