<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class FieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $virtualFieldProvider;

    /** @var FieldHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);

        $this->helper = new FieldHelper($this->virtualFieldProvider);
    }

    public function testGetFieldExprNotVirtual()
    {
        $entityClass = 'stdClass';
        $fieldName = 'some';
        $alias = 'alias1';

        $from = $this->createMock(From::class);
        $from->expects($this->once())
            ->method('getAlias')
            ->willReturn($alias);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getDQLPart')
            ->with('from')
            ->willReturn([$from]);

        $this->virtualFieldProvider->expects($this->once())
            ->method('isVirtualField')
            ->with($entityClass, $fieldName)
            ->willReturn(false);

        $this->assertEquals('alias1.some', $this->helper->getFieldExpr($entityClass, $qb, $fieldName));
    }

    /**
     * @dataProvider virtualFieldsProvider
     */
    public function testGetFieldExprVirtual(
        string $entityClass,
        string $fieldName,
        string $alias,
        array $fieldConfig,
        array $joins,
        string $expected
    ) {
        $from = $this->createMock(From::class);
        $from->expects($this->atLeastOnce())
            ->method('getAlias')
            ->willReturn($alias);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeastOnce())
            ->method('getDQLPart')
            ->willReturnMap([
                ['from', [$from]],
                ['join', $joins]
            ]);

        $this->virtualFieldProvider->expects($this->once())
            ->method('isVirtualField')
            ->with($entityClass, $fieldName)
            ->willReturn(true);
        $this->virtualFieldProvider->expects($this->once())
            ->method('getVirtualFieldQuery')
            ->with($entityClass, $fieldName)
            ->willReturn($fieldConfig);

        $this->assertEquals($expected, $this->helper->getFieldExpr($entityClass, $qb, $fieldName));
    }

    public function virtualFieldsProvider(): array
    {
        return [
            'has_join' => [
                'stdClass',
                'field',
                't1',
                [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'entity.emails',
                                'alias' => 'emails',
                                'conditionType' => 'WITH',
                                'condition' => 'emails.primary = true'
                            ]
                        ]
                    ],
                    'select' => [
                        'expr' => 'emails.email'
                    ]
                ],
                [
                    't1' => [
                        new Join('LEFT', 't1.emails', 't2', 'WITH', 't2.primary = true'),
                        new Join('LEFT', 't1.phones', 't3', 'WITH', 't3.primary = true'),
                        new Join('INNER', 't1.account', 't4', 'WITH', 't4.id = t1.account_id'),
                    ]
                ],
                't2.email'
            ],
            'empty_qb' => [
                'stdClass',
                'field',
                't1',
                [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'entity.emails',
                                'alias' => 'emails',
                                'conditionType' => 'WITH',
                                'condition' => 'emails.primary = true'
                            ]
                        ]
                    ],
                    'select' => [
                        'expr' => 'emails.email'
                    ]
                ],
                [],
                'emails.email'
            ]
        ];
    }
}
