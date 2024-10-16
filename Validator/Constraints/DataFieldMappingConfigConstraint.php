<?php

namespace Oro\Bundle\DotmailerBundle\Validator\Constraints;

use Oro\Bundle\DotmailerBundle\Validator\DataFieldMappingConfigValidator;
use Symfony\Component\Validator\Constraint;

class DataFieldMappingConfigConstraint extends Constraint
{
    public $errorPath = null;

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return DataFieldMappingConfigValidator::ALIAS;
    }
}
