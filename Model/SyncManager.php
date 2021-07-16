<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Marks address book contacts as updated to make sure updated field values are synced to Dotmailer.
 */
class SyncManager
{
    const FORCE_SYNC_NONE = 'None';
    const FORCE_SYNC_VIRTUALS_ONLY = 'VirtualOnly';
    const FORCE_SYNC_ALWAYS = 'Always';

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        MappingProvider $mappingProvider,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->configManager = $configManager;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Mark address book contacts as updated to make sure updated field values are synced to Dotmailer
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function forceMarkEntityUpdate()
    {
        $forceSync = $this->configManager->get('oro_dotmailer.force_sync_for_virtual_fields');
        if (!$forceSync || $forceSync === static::FORCE_SYNC_NONE) {
            return;
        }
        /** @var AddressBookRepository $addressBookRepository */
        $addressBookRepository = $this->doctrineHelper->getEntityRepositoryForClass(AddressBook::class);
        $addressBooks = $addressBookRepository->getAddressBooksWithML();
        $classesForForceUpdate = [];
        $channels = [];
        foreach ($addressBooks as $addressBook) {
            $channelId = $addressBook->getChannel()->getId();
            $channels[$channelId] = $addressBook->getChannel();
            $class = $addressBook->getMarketingList()->getEntity();
            $hasVirtualFieldsMapped = $this->mappingProvider->entityHasVirutalFieldsMapped(
                $channelId,
                $class
            );
            $hasMappings = $this->mappingProvider->getExportMappingConfigForEntity($class, $channelId);
            if (($forceSync === static::FORCE_SYNC_ALWAYS && $hasMappings) || $hasVirtualFieldsMapped) {
                if (!isset($classesForForceUpdate[$channelId])) {
                    $classesForForceUpdate[$channelId] = [];
                }
                $classesForForceUpdate[$channelId][] = $class;
            }
        }

        if ($this->dispatcher && $this->dispatcher->hasListeners(ForceSyncEvent::NAME)) {
            $event = new ForceSyncEvent($classesForForceUpdate);
            $this->dispatcher->dispatch($event, ForceSyncEvent::NAME);
            $classesForForceUpdate = $event->getClasses();
        }

        if ($classesForForceUpdate) {
            /** @var AddressBookContactRepository $addressBookContactRepository */
            $addressBookContactRepository = $this->doctrineHelper
                ->getEntityRepositoryForClass(AddressBookContact::class);
            foreach ($classesForForceUpdate as $channeld => $classes) {
                $classes = array_unique($classes);
                $addressBookContactRepository->bulkUpdateEntityUpdatedFlag($classes, $channels[$channeld]);
            }
        }
    }
}
