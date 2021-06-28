<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractMarketingListItemIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UpdateEntityFieldsFromContactIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Prepares dotmailer QB contacts for iteration
 */
class UpdateEntityFieldsFromContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $marketingListItemsQueryBuilderProvider = $this->createMock(MarketingListItemsQueryBuilderProvider::class);
        $context = $this->createMock(ContextInterface::class);
        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($addressBookOriginId = 42);
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];

        $marketingListItemsQueryBuilderProvider->expects($this->any())
            ->method('getAddressBook')
            ->willReturn($addressBook);

        $expectedItems = [
            ['id' => 23, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $iterator = new UpdateEntityFieldsFromContactIterator(
            $addressBook,
            $marketingListItemsQueryBuilderProvider,
            $context
        );
        $contactsToUpdateFromQB = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->any())
            ->method('getScheduledForEntityFieldsUpdateQB')
            ->with($addressBook)
            ->willReturn($contactsToUpdateFromQB);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroDotmailerBundle:Contact')
            ->willReturn($repository);

        $iterator->setRegistry($registry);
        $iterator->setBatchSize(1);

        $contactsToUpdateFromQB->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['execute'])
            ->addMethods(['useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->willReturnSelf();

        $executeMap = [
            [$firstItem],
            [$secondItem],
            []
        ];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function () use (&$executeMap) {
                $result = current($executeMap);
                next($executeMap);

                return $result;
            });

        $contactsToUpdateFromQB->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testIteratorWithCreateNewEntities()
    {
        $marketingListItemsQueryBuilderProvider = $this->createMock(MarketingListItemsQueryBuilderProvider::class);
        $context = $this->createMock(ContextInterface::class);
        $addressBook = $this->createMock(AddressBook::class);
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->willReturn($addressBookOriginId = 42);
        $addressBook->expects($this->any())
            ->method('isCreateEntities')
            ->willReturn(true);
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];
        $expectedItems = [
            ['id' => 23, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $marketingListItemsQueryBuilderProvider->expects($this->any())
            ->method('getAddressBook')
            ->willReturn($addressBook);

        $iterator = new UpdateEntityFieldsFromContactIterator(
            $addressBook,
            $marketingListItemsQueryBuilderProvider,
            $context
        );
        $contactsToUpdateFromQB = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->any())
            ->method('getScheduledForEntityFieldsUpdateQB')
            ->with($addressBook)
            ->willReturn($contactsToUpdateFromQB);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroDotmailerBundle:Contact')
            ->willReturn($repository);
        $iterator->setRegistry($registry);
        $iterator->setBatchSize(1);

        $emailQb = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $andX = new Andx();
        $expr->expects($this->exactly(3))
            ->method('andX')
            ->willReturn($andX);
        $expr->expects($this->atLeastOnce())
            ->method('notIn')
            ->with('contact.email', 'Email QB DQL');
        $emailQb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);
        $emailQb->expects($this->any())
            ->method('getDQL')
            ->willReturn('Email QB DQL');

        $parameter = new Parameter('organiztion', 1);
        $emailQb->expects($this->any())
            ->method('getParameter')
            ->with('organization')
            ->willReturn($parameter);
        $marketingListItemsQueryBuilderProvider->expects($this->exactly(3))
            ->method('getFindEntityEmailsQB')
            ->with($addressBook)
            ->willReturn($emailQb);
        $contactsToUpdateFromQB->expects($this->exactly(6))
            ->method('setParameter')
            ->withConsecutive(
                ['newEntity', true],
                ['organiztion', 1],
                ['newEntity', true],
                ['organiztion', 1],
                ['newEntity', true],
                ['organiztion', 1],
            );
        $contactsToUpdateFromQB->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['execute'])
            ->addMethods(['useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->willReturnSelf();

        $executeMap = [[$firstItem],[$secondItem],[]];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function () use (&$executeMap) {
                $result = current($executeMap);
                next($executeMap);

                return $result;
            });
        $contactsToUpdateFromQB->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
