<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class ContactExportQBAdapterRegistry
{
    const ADAPTER_PRIORITY_KEY = 'priority';
    const ADAPTER_SERVICE_KEY = 'adapter';
    /**
     * @var array
     */
    protected $adapters = [];

    /**
     * @var bool Is providers sorted by priority
     */
    protected $isSorted;

    /**
     * @param AddressBook $addressBook
     *
     * @return ContactExportQBAdapterInterface
     */
    public function getAdapterByAddressBook(AddressBook $addressBook)
    {
        if (!$this->isSorted) {
            $sortAdaptersDelegate = function ($firstItem, $secondItem) {
                if ($firstItem[self::ADAPTER_PRIORITY_KEY] == $secondItem[self::ADAPTER_PRIORITY_KEY]) {
                    return 0;
                }

                return ($firstItem[self::ADAPTER_PRIORITY_KEY] < $secondItem[self::ADAPTER_PRIORITY_KEY]) ? 1 : -1;
            };
            uasort($this->adapters, $sortAdaptersDelegate);
        }

        foreach ($this->adapters as $adapter) {
            /** @var ContactExportQBAdapterInterface $adapter */
            $adapter = $adapter[self::ADAPTER_SERVICE_KEY];
            if ($adapter->isApplicable($addressBook)) {
                return $adapter;
            }
        }

        throw new RuntimeException("Provider for Address Book '{$addressBook->getId()}' not exist");
    }

    /**
     * @return array
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param ContactExportQBAdapterInterface $adapter
     * @param int                             $priority
     *
     * @return $this
     */
    public function addAdapter(ContactExportQBAdapterInterface $adapter, $priority)
    {
        $this->isSorted = false;
        $this->adapters[] = [self::ADAPTER_PRIORITY_KEY => $priority, self::ADAPTER_SERVICE_KEY => $adapter];

        return $this;
    }

    /**
     * @param array $adapters ['priority' => $priority, 'adapter' => $adapter]
     *
     * @return ContactExportQBAdapterRegistry
     */
    public function setAdapters(array $adapters)
    {
        $this->isSorted = false;

        $this->validateAdapters($adapters);
        $this->adapters = $adapters;

        return $this;
    }

    protected function validateAdapters(array $adapters)
    {
        foreach ($adapters as $adapter) {
            $isAdapterFormatIncorrect = !is_array($adapter)
                || !isset($adapter[self::ADAPTER_PRIORITY_KEY])
                || !isset($adapter[self::ADAPTER_SERVICE_KEY]);

            if ($isAdapterFormatIncorrect) {
                throw new RuntimeException('Incorrect adapter format.');
            }

            $adapter = $adapter[self::ADAPTER_SERVICE_KEY];
            if (!$adapter instanceof ContactExportQBAdapterInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Instance of %s required. Instance of %s given.',
                        'Oro\Bundle\DotmailerBundle\Provider\ContactExportQBAdapterInterface',
                        is_object($adapter) ? get_class($adapter) : gettype($adapter)
                    )
                );
            }
        }
    }
}
