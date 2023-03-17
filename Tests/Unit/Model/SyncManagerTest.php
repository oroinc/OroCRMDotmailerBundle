<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookRepository;
use Oro\Bundle\DotmailerBundle\Model\ForceSyncEvent;
use Oro\Bundle\DotmailerBundle\Model\SyncManager;
use Oro\Bundle\DotmailerBundle\Provider\MappingProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SyncManager */
    private $syncManager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->mappingProvider = $this->createMock(MappingProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->syncManager = new SyncManager($this->doctrineHelper, $this->mappingProvider, $this->configManager);
    }

    public function testForceMarkEntityUpdateNone(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->willReturn(SyncManager::FORCE_SYNC_NONE);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateVirtualOnly(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->willReturn(SyncManager::FORCE_SYNC_VIRTUALS_ONLY);
        $addressBookRepository = $this->createMock(AddressBookRepository::class);
        $channelId = 1;
        $channel = $this->getChannel($channelId);
        $addressBook = $this->getAddressBook($channel, 'EntityClass');
        $addressBookRepository->expects($this->once())
            ->method('getAddressBooksWithML')
            ->willReturn([$addressBook]);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [AddressBook::class, $addressBookRepository],
                [AddressBookContact::class, $addressBookContactRepository],
            ]);

        $this->mappingProvider->expects($this->once())
            ->method('entityHasVirutalFieldsMapped')
            ->with($channelId, 'EntityClass')
            ->willReturn(true);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateAlways(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->willReturn(SyncManager::FORCE_SYNC_ALWAYS);
        $addressBookRepository = $this->createMock(AddressBookRepository::class);
        $addressBooks = [];
        $channelId = 1;
        $channel = $this->getChannel($channelId);
        $addressBooks[] = $this->getAddressBook($channel, 'EntityClass');
        $addressBooks[] = $this->getAddressBook($channel, 'AnotherEntityClass');
        $addressBookRepository->expects($this->once())
            ->method('getAddressBooksWithML')
            ->willReturn($addressBooks);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [AddressBook::class, $addressBookRepository],
                [AddressBookContact::class, $addressBookContactRepository],
            ]);
        $this->mappingProvider->expects($this->any())
            ->method('getExportMappingConfigForEntity')
            ->willReturnMap([
                ['EntityClass', 1, ['EntityClass' => 1]],
                ['AnotherEntityClass', 1, []],
            ]);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateAlwaysWithEvent(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->willReturn(SyncManager::FORCE_SYNC_ALWAYS);
        $addressBookRepository = $this->createMock(AddressBookRepository::class);
        $addressBooks = [];
        $channelId = 1;
        $channel = $this->getChannel($channelId);
        $addressBooks[] = $this->getAddressBook($channel, 'EntityClass');
        $addressBooks[] = $this->getAddressBook($channel, 'AnotherEntityClass');
        $addressBookRepository->expects($this->once())
            ->method('getAddressBooksWithML')
            ->willReturn($addressBooks);

        $addressBookContactRepository = $this->createMock(AddressBookContactRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [AddressBook::class, $addressBookRepository],
                [AddressBookContact::class, $addressBookContactRepository],
            ]);
        $eventData = [
            1 => ['EntityClass']
        ];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(ForceSyncEvent::NAME)
            ->willReturn(true);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(ForceSyncEvent::class),
                ForceSyncEvent::NAME
            )
            ->willReturnCallback(function (ForceSyncEvent $event) use ($eventData) {
                $event->setClasses($eventData);

                return $event;
            });
        $this->syncManager->setDispatcher($dispatcher);

        $addressBookContactRepository->expects($this->once())
            ->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    private function getAddressBook(Channel $channel, string $entityClass): AddressBook
    {
        $marketingList = new MarketingList();
        $marketingList->setEntity($entityClass);
        $addressBook = new AddressBook();
        $addressBook->setMarketingList($marketingList);
        $addressBook->setChannel($channel);

        return $addressBook;
    }

    private function getChannel(int $id): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);

        return $channel;
    }
}
