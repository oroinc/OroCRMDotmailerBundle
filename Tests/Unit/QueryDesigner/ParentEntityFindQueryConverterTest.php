<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactAddress;
use Oro\Bundle\DotmailerBundle\QueryDesigner\ParentEntityFindQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;

class ParentEntityFindQueryConverterTest extends OrmQueryConverterTestCase
{
    public function testConvert()
    {
        $doctrineHelper = $this->getDoctrineHelper(
            [
                Contact::class => [],
                ContactAddress::class => [],
            ],
            [
                Contact::class => ['id'],
                ContactAddress::class => ['id'],
            ]
        );

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $doctrineHelper->getEntityManagerForClass(Contact::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($em));

        $converter = new ParentEntityFindQueryConverter(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            $doctrineHelper
        );

        $columns = [
            ['name' => 'addresses+Oro\Bundle\ContactBundle\Entity\ContactAddress::postalCode', 'value' => 42]
        ];
        $qb = $converter->convert(Contact::class, $columns);

        $this->assertEquals([new From(Contact::class, 't1')], $qb->getDQLPart('from'));
        $this->assertEquals(['t1' => [new Join(Join::LEFT_JOIN, 't1.addresses', 't2')]], $qb->getDQLPart('join'));
        $this->assertEquals([
            new Select([sprintf('t1.id as %s', ParentEntityFindQueryConverter::PARENT_ENTITY_ID_ALIAS)]),
        ], $qb->getDQLPart('select'));
        $this->assertEquals(new Andx(['t2.id = :value']), $qb->getDQLPart('where'));
        $this->assertEquals(new Parameter('value', 42), $qb->getParameter('value'));
    }
}
