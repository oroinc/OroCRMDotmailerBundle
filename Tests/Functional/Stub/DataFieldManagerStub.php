<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Stub;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;

class DataFieldManagerStub extends DataFieldManager
{
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createOriginDataField(DataField $field)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function removeOriginDataField(DataField $field)
    {
    }
}
