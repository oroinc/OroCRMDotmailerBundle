<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Provider;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDotmailerContactData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MarketingListItemsQueryBuilderProviderTest extends WebTestCase
{
    private MarketingListItemsQueryBuilderProvider $queryBuilderProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadDotmailerContactData::class]);

        $this->queryBuilderProvider = $this->getContainer()
            ->get('oro_dotmailer.provider.marketing_list_items.query_builder');
    }

    public function testGetMarketingListItemsQB()
    {
        /** @var AddressBook $addressBook */
        $addressBook = $this->getReference('oro_dotmailer.address_book.fifth');

        $qb = $this->queryBuilderProvider->getMarketingListItemsQB($addressBook, []);
        $result = $qb->andWhere($qb->expr()->isNotNull('dm_contact.email'))
            ->getQuery()
            ->getArrayResult();
        $emails = [];
        foreach ($result as $item) {
            $emails[] = $item['email'];
        }

        $this->assertEquals([
            'john.case@example.com',
            'alex.case@example.com',
            'allen.case@example.com',
        ], $emails);
    }

    public function testGetRemovedMarketingListItemsQB()
    {
        /** @var AddressBook $addressBook */
        $addressBook = $this->getReference('oro_dotmailer.address_book.fifth');

        $qb = $this->queryBuilderProvider->getRemovedMarketingListItemsQB($addressBook, []);
        $result = $qb->getQuery()->getArrayResult();

        $this->assertCount(1, $result);
    }
}
