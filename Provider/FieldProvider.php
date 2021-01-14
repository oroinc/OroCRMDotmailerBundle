<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class FieldProvider extends EntityFieldProvider
{
    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        //do not allow non single associations to be used in the mapping
        if (!$metadata->isSingleValuedAssociation($associationName)) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }
}
