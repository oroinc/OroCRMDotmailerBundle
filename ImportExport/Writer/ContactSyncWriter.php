<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Oro\Bundle\DotmailerBundle\ImportExport\Processor\ContactSyncProcessor;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class ContactSyncWriter extends ImportWriter
{
    /**
     * @var OptionalListenerManager
     */
    protected $optionalListenerManager;

    public function setOptionalListenerManager(OptionalListenerManager $optionalListenerManager)
    {
        $this->optionalListenerManager = $optionalListenerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $context = $this->contextRegistry
            ->getByStepExecution($this->stepExecution);

        /**
         * Clear already read items raw values
         */
        $context->setValue(ContactSyncProcessor::CURRENT_BATCH_READ_ITEMS, []);

        $this->toggleOptionalListeners(false);

        parent::write($items);

        $this->toggleOptionalListeners();
    }

    /**
     * @param bool $enable
     */
    protected function toggleOptionalListeners($enable = true)
    {
        $knownListeners  = $this->optionalListenerManager->getListeners();
        foreach ($knownListeners as $listenerId) {
            if ($enable) {
                $this->optionalListenerManager->enableListener($listenerId);
            } else {
                $this->optionalListenerManager->disableListener($listenerId);
            }
        }
    }
}
