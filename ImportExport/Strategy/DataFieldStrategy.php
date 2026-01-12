<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\DataField;

/**
 * Handles the import strategy for data field entities from Dotmailer.
 */
class DataFieldStrategy extends AddOrReplaceStrategy
{
    public const EXISTING_DATAFIELDS_NAMES = 'existingDataFieldsNames';

    #[\Override]
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if ($entity instanceof $this->entityName) {
            //store imported datafield names in context
            $existingDataFieldsNames = $this->context->getValue(self::EXISTING_DATAFIELDS_NAMES) ?: [];
            $existingDataFieldsNames[] = $entity->getName();
            $this->context->setValue(self::EXISTING_DATAFIELDS_NAMES, $existingDataFieldsNames);
        }

        return parent::findExistingEntity($entity, $searchContext);
    }
}
