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
     * {@inheritdoc}
     */
    public function createOriginDataField(DataField $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function removeOriginDataField(DataField $field)
    {
    }
}
