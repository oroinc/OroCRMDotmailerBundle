<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactIterator;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;

class UnsubscribedContactDataConverter implements DataConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $contact = $importedRecord['suppressedcontact'];

        $contactFields = [
            'originId'           => 'id',
            'email'              => 'email',
            'lastSubscribedDate' => 'LASTSUBSCRIBED',
        ];

        $convertedContact = [];
        foreach ($contactFields as $destinationFieldName => $sourceFieldName) {
            $this->mapField($convertedContact, $contact, $destinationFieldName, $sourceFieldName);
        }

        if (isset($contact['optintype'])) {
            $convertedContact['opt_in_type'] = [
                'id' => $contact['optintype']
            ];
        }
        if (isset($contact['emailtype'])) {
            $convertedContact['email_type'] = [
                'id' => $contact['emailtype']
            ];
        }

        $convertedData['contact'] = $convertedContact;

        $convertedData['status'] = [
            'id' => $importedRecord['reason']
        ];
        $this->mapField($convertedData, $importedRecord, 'unsubscribedDate', 'dateremoved');
        $this->mapField(
            $convertedData,
            $importedRecord,
            UnsubscribedContactIterator::ADDRESS_BOOK_KEY,
            UnsubscribedContactIterator::ADDRESS_BOOK_KEY
        );

        return $convertedData;
    }

    /**
     * @param array  $destination
     * @param array  $source
     * @param string $destinationFieldName
     * @param string $sourceFieldName
     */
    protected function mapField(array &$destination, array $source, $destinationFieldName, $sourceFieldName)
    {
        $destination[$destinationFieldName] = isset($source[$sourceFieldName]) ? $source[$sourceFieldName] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
