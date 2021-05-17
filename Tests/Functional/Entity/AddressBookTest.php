<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AddressBookTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadAddressBookData::class]);
        $this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');
    }

    public function testShouldScheduleSyncWhenMarketingListIsChanged()
    {
        /** @var AddressBook $addressBook */
        $addressBook = $this->getReference('oro_dotmailer.address_book.first');

        /** @var MarketingList $marketingList */
        $marketingList = $this->getReference('oro_dotmailer.marketing_list.first');

        //guard
        self::assertNotSame($addressBook->getMarketingList(), $marketingList);

        $addressBook->setMarketingList($marketingList);

        $this->getEntityManager()->persist($addressBook);
        $this->getEntityManager()->flush();

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::SYNC_INTEGRATION);

        self::assertCount(1, $traces);
        self::assertEquals([
            'integration_id' => $addressBook->getChannel()->getId(),
            'connector' => null,
            'connector_parameters' => [
                'address-book' => $addressBook->getId()
            ],
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
