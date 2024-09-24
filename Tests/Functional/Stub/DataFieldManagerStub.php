<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Stub;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;

class DataFieldManagerStub extends DataFieldManager
{
    public function __construct()
    {
    }

    #[\Override]
    public function createOriginDataField(DataField $field)
    {
    }

    #[\Override]
    public function removeOriginDataField(DataField $field)
    {
    }
}
