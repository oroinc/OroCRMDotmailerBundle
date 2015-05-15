<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

class ContactDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'id'            => 'originId',
            'status'        => 'status:id',
            'optInType'     => 'opt_in_type:id',
            'emailType'     => 'email_type:id',
            'FIRSTNAME'     => 'firstName',
            'LASTNAME'      => 'lastName',
            'GENDER'        => 'gender',
            'FULLNAME'      => 'fullname',
            'POSTCODE'      => 'postcode',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
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
