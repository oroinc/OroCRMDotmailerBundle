<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class DataFieldIterator extends AbstractIterator
{
    /**
     * @var IResources
     */
    protected $resources;

    public function __construct(IResources $resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $apiDataFieldsList = $this->resources->GetDataFields()->toArray();

        return $apiDataFieldsList;
    }
}
