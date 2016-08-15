<?php
namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * @dbIsolationPerTest
 */
class AddressBookTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadAddressBookData::class]);
        $this->getMessageProducer()->clearTraces();
    }

    public function testShouldScheduleSyncWhenMarketingListIsChanged()
    {
        /** @var AddressBook $addressBook */
        $addressBook = $this->getReference('orocrm_dotmailer.address_book.first');

        /** @var MarketingList $marketingList */
        $marketingList = $this->getReference('orocrm_dotmailer.marketing_list.first');

        //guard
        self::assertNotSame($addressBook->getMarketingList(), $marketingList);

        $addressBook->setMarketingList($marketingList);

        $this->getEntityManager()->persist($addressBook);
        $this->getEntityManager()->flush();

        $traces = $this->getMessageProducer()->getTopicTraces(Topics::SYNC_INTEGRATION);

        self::assertCount(1, $traces);
        self::assertEquals([
            'integration_id' => $addressBook->getChannel()->getId(),
            'connector' => null,
            'connector_parameters' => [
                'address-book' => $addressBook->getId()
            ],
            'transport_batch_size' => 100,
        ], $traces[0]['message']);
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }
}
