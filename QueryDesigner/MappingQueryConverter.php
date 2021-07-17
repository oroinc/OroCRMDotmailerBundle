<?php

namespace Oro\Bundle\DotmailerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;

/**
 * Adds mapping columns to an ORM query.
 */
class MappingQueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    /**
     * Add mapping columns to the query builder
     */
    public function addMappingColumns(QueryBuilder $qb, string $entity, array $columns, array $compositeColumns): void
    {
        $source = new QueryDesigner(
            $entity,
            $this->encodeDefinition(['columns' => $columns, 'composite_columns' => $compositeColumns])
        );

        $rootAliases = $qb->getRootAliases();
        $this->context()->setRootEntityAlias(reset($rootAliases));
        $this->context()->setQueryBuilder($qb);
        $this->doConvert($source);
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['composite_columns'] as $compositeColumn) {
            $columnExpressions = [];
            foreach ($compositeColumn['columns'] as $columnName) {
                $fieldName = $this->getFieldName($columnName);
                $tableAlias = $this->getTableAliasForColumn($columnName);
                $columnExpressions[] = $this->buildColumnExpression($columnName, $tableAlias, $fieldName);
            }
            if (count($columnExpressions) > 1) {
                $columnExpression = sprintf("CONCAT_WS('%s', %s)", ' ', implode(', ', $columnExpressions));
            } else {
                $columnExpression = current($columnExpressions);
            }
            $context->getQueryBuilder()->addSelect(sprintf('%s as %s', $columnExpression, $compositeColumn['alias']));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addTableAliasForRootEntity(): void
    {
        $this->context()->setRootTableAlias($this->context()->getRootEntityAlias());
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void {
        //not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement(string $entityClass, string $tableAlias): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases(array $tableAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases(array $columnAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement(): void
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn(string $columnAlias): void
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        // do nothing, order could not change results
    }

    /**
     * {@inheritdoc}
     */
    protected function beginWhereGroup(): void
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function endWhereGroup(): void
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereOperator(string $operator): void
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereCondition(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        ?string $columnAlias,
        string $filterName,
        array $filterData,
        $functionExpr = null
    ): void {
        // do nothing, where is not used
    }
}
