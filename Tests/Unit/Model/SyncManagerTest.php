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
use Oro\Component\Testing\Unit\EntityTrait;

class SyncManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var MappingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mappingProvider;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var SyncManager
     */
    protected $syncManager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mappingProvider = $this->getMockBuilder(MappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->syncManager = new SyncManager($this->doctrineHelper, $this->mappingProvider, $this->configManager);
    }

    public function testForceMarkEntityUpdateNone()
    {
        $this->configManager->expects($this->once())->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->will($this->returnValue(SyncManager::FORCE_SYNC_NONE));
        $this->doctrineHelper->expects($this->never())->method('getEntityRepositoryForClass');

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateVirtualOnly()
    {
        $this->configManager->expects($this->once())->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->will($this->returnValue(SyncManager::FORCE_SYNC_VIRTUALS_ONLY));
        $addressBookRepository = $this->getMockBuilder(AddressBookRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channelId = 1;
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => $channelId]);
        $addressBook = $this->createAddressBook($channel, 'EntityClass');
        $addressBookRepository->expects($this->once())->method('getAddressBooksWithML')->will(
            $this->returnValue([$addressBook])
        );

        $addressBookContactRepository = $this->getMockBuilder(AddressBookContactRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')->will(
            $this->returnValueMap(
                [
                    [AddressBook::class, $addressBookRepository],
                    [AddressBookContact::class, $addressBookContactRepository],
                ]
            )
        );

        $this->mappingProvider->expects($this->once())->method('entityHasVirutalFieldsMapped')
            ->with($channelId, 'EntityClass')
            ->will($this->returnValue(true));

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateAlways()
    {
        $this->configManager->expects($this->once())->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->will($this->returnValue(SyncManager::FORCE_SYNC_ALWAYS));
        $addressBookRepository = $this->getMockBuilder(AddressBookRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressBooks = [];
        $channelId = 1;
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => $channelId]);
        $addressBooks[] = $this->createAddressBook($channel, 'EntityClass');
        $addressBooks[] = $this->createAddressBook($channel, 'AnotherEntityClass');
        $addressBookRepository->expects($this->once())->method('getAddressBooksWithML')->will(
            $this->returnValue($addressBooks)
        );

        $addressBookContactRepository = $this->getMockBuilder(AddressBookContactRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')->will(
            $this->returnValueMap(
                [
                    [AddressBook::class, $addressBookRepository],
                    [AddressBookContact::class, $addressBookContactRepository],
                ]
            )
        );
        $this->mappingProvider->expects($this->any())->method('getExportMappingConfigForEntity')
            ->will($this->returnValueMap([
                ['EntityClass', 1, true],
                ['AnotherEntityClass', 1, false],
            ]));

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    public function testForceMarkEntityUpdateAlwaysWithEvent()
    {
        $this->configManager->expects($this->once())->method('get')
            ->with('oro_dotmailer.force_sync_for_virtual_fields')
            ->will($this->returnValue(SyncManager::FORCE_SYNC_ALWAYS));
        $addressBookRepository = $this->getMockBuilder(AddressBookRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressBooks = [];
        $channelId = 1;
        $channel = $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => $channelId]);
        $addressBooks[] = $this->createAddressBook($channel, 'EntityClass');
        $addressBooks[] = $this->createAddressBook($channel, 'AnotherEntityClass');
        $addressBookRepository->expects($this->once())->method('getAddressBooksWithML')->will(
            $this->returnValue($addressBooks)
        );

        $addressBookContactRepository = $this->getMockBuilder(AddressBookContactRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityRepositoryForClass')->will(
            $this->returnValueMap(
                [
                    [AddressBook::class, $addressBookRepository],
                    [AddressBookContact::class, $addressBookContactRepository],
                ]
            )
        );
        $event = new ForceSyncEvent([]);
        $eventData = [
            1 => ['EntityClass']
        ];
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableArgumentCloning()
            ->getMock();
        $dispatcher->expects($this->once())->method('hasListeners')->with(ForceSyncEvent::NAME)
            ->will($this->returnValue(true));
        $dispatcher->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(ForceSyncEvent::class),
            ForceSyncEvent::NAME
        )->will($this->returnCallback(function (ForceSyncEvent $event, $name) use ($eventData) {
            $event->setClasses($eventData);
        }));
        $this->syncManager->setDispatcher($dispatcher);

        $addressBookContactRepository->expects($this->once())->method('bulkUpdateEntityUpdatedFlag')
            ->with(['EntityClass'], $channel);

        $this->syncManager->forceMarkEntityUpdate();
    }

    /**
     * @param Channel $channel
     * @param string $entityClass
     * @return AddressBook
     */
    protected function createAddressBook(Channel $channel, $entityClass)
    {
        $marketingList = new MarketingList();
        $marketingList->setEntity($entityClass);
        $addressBook = new AddressBook();
        $addressBook->setMarketingList($marketingList);
        $addressBook->setChannel($channel);

        return $addressBook;
    }
}
