<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Stub;

use Oro\Bundle\DotmailerBundle\Entity\DataField;

class DataFieldStub extends DataField
{
    /**
     * @var object
     */
    protected $type;

    /**
     * @var object
     */
    protected $visibility;

    /**
     * @return object
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param object $type
     * @return DataFieldStub
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return object
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param object $visibility
     * @return DataFieldStub
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }
}
