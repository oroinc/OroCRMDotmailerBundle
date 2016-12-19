<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

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
