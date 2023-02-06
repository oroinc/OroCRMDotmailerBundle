<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Stub;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class DataFieldStub extends DataField
{
    private ?AbstractEnumValue $type = null;
    private ?AbstractEnumValue $visibility = null;

    public function getType(): ?AbstractEnumValue
    {
        return $this->type;
    }

    public function setType(AbstractEnumValue $enumValue): self
    {
        $this->type = $enumValue;

        return $this;
    }

    public function getVisibility(): ?AbstractEnumValue
    {
        return $this->visibility;
    }

    public function setVisibility(AbstractEnumValue $enumValue): self
    {
        $this->visibility = $enumValue;

        return $this;
    }
}
