<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class AddressBookIterator extends AbstractIterator
{
    /**
     * {@inheritdoc}
     */
    protected $batchSize = 100;

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
        $apiAddressBookList = $this->resources->GetAddressBooks($take, $skip)
            ->toArray();
        return $apiAddressBookList;
    }
}
