<?php

namespace Oro\Bundle\DotmailerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\DotmailerBundle\Validator\DataFieldMappingConfigValidator;

class DataFieldMappingConfigConstraint extends Constraint
{
    public $errorPath = null;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return DataFieldMappingConfigValidator::ALIAS;
    }
}
