<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Iterator;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractMarketingListItemIterator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\UpdateEntityFieldsFromContactIterator;

/**
 * Prepares dotmailer QB contacts for iteration
 */
class UpdateEntityFieldsFromContactIteratorTest extends \PHPUnit\Framework\TestCase
{
    public function testIterator()
    {
        $marketingListItemsQueryBuilderProvider = $this->getMockBuilder(
            'Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->will($this->returnValue($addressBookOriginId = 42));
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];

        $marketingListItemsQueryBuilderProvider->expects($this->any())->method('getAddressBook')
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
        $contactsToUpdateFromQB = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('getScheduledForEntityFieldsUpdateQB')
            ->with($addressBook)
            ->will($this->returnValue($contactsToUpdateFromQB));
        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $registry->expects($this->any())->method('getRepository')->with('OroDotmailerBundle:Contact')
            ->will($this->returnValue($repository));

        $iterator->setRegistry($registry);
        $iterator->setBatchSize(1);

        $contactsToUpdateFromQB->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['execute', 'useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->will($this->returnSelf());

        $executeMap = [
            [$firstItem],
            [$secondItem],
            []
        ];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function () use (&$executeMap) {
                        $result = current($executeMap);
                        next($executeMap);

                        return $result;
                    }
                )
            );

        $contactsToUpdateFromQB->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }

    public function testIteratorWithCreateNewEntities()
    {
        $marketingListItemsQueryBuilderProvider = $this->getMockBuilder(
            'Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $addressBook = $this->createMock('Oro\Bundle\DotmailerBundle\Entity\AddressBook');
        $addressBook->expects($this->any())
            ->method('getOriginId')
            ->will($this->returnValue($addressBookOriginId = 42));
        $addressBook->expects($this->any())
            ->method('isCreateEntities')
            ->will($this->returnValue(true));
        $firstItem = ['id' => 23];
        $secondItem = ['id' => 44];
        $expectedItems = [
            ['id' => 23, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
            ['id' => 44, AbstractMarketingListItemIterator::ADDRESS_BOOK_KEY => $addressBookOriginId],
        ];

        $marketingListItemsQueryBuilderProvider->expects($this->any())->method('getAddressBook')
            ->willReturn($addressBook);

        $iterator = new UpdateEntityFieldsFromContactIterator(
            $addressBook,
            $marketingListItemsQueryBuilderProvider,
            $context
        );
        $contactsToUpdateFromQB = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('getScheduledForEntityFieldsUpdateQB')
            ->with($addressBook)
            ->will($this->returnValue($contactsToUpdateFromQB));
        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $registry->expects($this->any())->method('getRepository')->with('OroDotmailerBundle:Contact')
            ->will($this->returnValue($repository));
        $iterator->setRegistry($registry);
        $iterator->setBatchSize(1);

        $emailQb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')->getMock();
        $andX = new Andx();
        $expr->expects($this->exactly(3))->method('andX')->will($this->returnValue($andX));
        $expr->expects($this->atLeastOnce())->method('notIn')->with('contact.email', 'Email QB DQL');
        $emailQb->expects($this->any())->method('expr')->will($this->returnValue($expr));
        $emailQb->expects($this->any())->method('getDQL')->will($this->returnValue('Email QB DQL'));

        $parameter = new Parameter('organiztion', 1);
        $emailQb->expects($this->any())->method('getParameter')->with('organization')->will(
            $this->returnValue($parameter)
        );
        $marketingListItemsQueryBuilderProvider->expects($this->exactly(3))->method('getFindEntityEmailsQB')
            ->with($addressBook)
            ->will($this->returnValue($emailQb));
        $contactsToUpdateFromQB->expects($this->at(1))->method('setParameter')->with('newEntity', true);
        $contactsToUpdateFromQB->expects($this->at(2))->method('setParameter')->with('organiztion', 1);
        $contactsToUpdateFromQB->expects($this->exactly(3))
            ->method('setMaxResults')
            ->with(1);
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['execute', 'useQueryCache'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->exactly(3))
            ->method('useQueryCache')
            ->will($this->returnSelf());

        $executeMap = [[$firstItem],[$secondItem],[]];
        $query->expects($this->exactly(3))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function () use (&$executeMap) {
                        $result = current($executeMap);
                        next($executeMap);

                        return $result;
                    }
                )
            );
        $contactsToUpdateFromQB->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        foreach ($iterator as $item) {
            $this->assertEquals(current($expectedItems), $item);
            next($expectedItems);
        }
    }
}
