<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

class UpdateEntityFieldsFromContactProcessor extends ImportProcessor
{
    const PROCESSED_CONTACT_IDS = 'processedContactIds';

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if ($item['entityClass']) {
            //set entit name based on address book contact's marketing list entity class
            $this->setEntityName($item['entityClass']);
            //store processed contact ids in the context
            $processedContactIds = $this->context->getValue(self::PROCESSED_CONTACT_IDS) ?: [];
            $processedContactIds[] = $item['contactId'];
            $this->context->setValue(self::PROCESSED_CONTACT_IDS, $processedContactIds);

            return parent::process($item);
        }

        return null;
    }
}
