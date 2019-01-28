<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\DataField;

class DataFieldStrategy extends AddOrReplaceStrategy
{
    const EXISTING_DATAFIELDS_NAMES = 'existingDataFieldsNames';

    /**
     * {@inheritdoc}
     */
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
