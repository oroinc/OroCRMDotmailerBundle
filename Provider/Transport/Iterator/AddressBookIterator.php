<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class AddressBookIterator extends AbstractIterator
{
    /**
     * @var DotmailerTransport
     */
    protected $transport;

    /**
     * @param DotmailerTransport $transport
     */
    public function __construct(DotmailerTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        return $this->transport->getAddressBooks($take, $skip);
    }
}
