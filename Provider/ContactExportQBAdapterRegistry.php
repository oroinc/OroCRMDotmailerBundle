<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

class ContactExportQBAdapterRegistry
{
    /**
     * @var array
     */
    protected $adapters = [];

    /**
     * @var bool Is providers sorted by priority
     */
    protected $isSorted;

    /**
     * @param ContactExportQBAdapterInterface $adapter
     * @param int                             $priority
     */
    public function addAdapter(ContactExportQBAdapterInterface $adapter, $priority)
    {
        $this->isSorted = false;
        $this->adapters[] = ['priority' => $priority, 'adapter' => $adapter];
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return ContactExportQBAdapterInterface
     */
    public function getAdapterByAddressBook(AddressBook $addressBook)
    {
        if (!$this->isSorted) {
            $sortProvidersDelegate = function ($firstItem, $secondItem) {
                if ($firstItem['priority'] == $secondItem['priority']) {
                    return 0;
                }

                return ($firstItem['priority'] < $secondItem['priority']) ? 1 : -1;
            };
            uasort($this->adapters, $sortProvidersDelegate);
        }

        foreach ($this->adapters as $adapter) {
            /** @var ContactExportQBAdapterInterface $adapter */
            $adapter = $adapter['adapter'];
            if ($adapter->isApplicable($addressBook)) {
                return $adapter;
            }
        }

        throw new RuntimeException("Provider for Address Book '{$addressBook->getId()}' not exist");
    }
}
