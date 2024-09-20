<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Stub;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;

class DataFieldStub extends DataField
{
    private ?EnumOptionInterface $type = null;
    private ?EnumOptionInterface $visibility = null;

    public function getType(): ?EnumOptionInterface
    {
        return $this->type;
    }

    public function setType(EnumOptionInterface $enumOption): self
    {
        $this->type = $enumOption;

        return $this;
    }

    public function getVisibility(): ?EnumOptionInterface
    {
        return $this->visibility;
    }

    public function setVisibility(EnumOptionInterface $enumOption): self
    {
        $this->visibility = $enumOption;

        return $this;
    }
}
