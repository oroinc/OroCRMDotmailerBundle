<?php

namespace Oro\Bundle\DotmailerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\AbstractOrmQueryConverter;

class MappingQueryConverter extends AbstractOrmQueryConverter
{
    const TABLE_ALIAS_TEMPLATE  = 'tm%d';

    /** @var QueryBuilder */
    protected $qb;

    /** @var string */
    protected $rootEntityAlias;

    /**
     * Add mapping columns to the query builder
     *
     * @param QueryBuilder $qb
     * @param string $entity
     * @param array $columns
     * @param array $compositeColumns
     */
    public function addMappingColumns(QueryBuilder $qb, $entity, $columns, $compositeColumns)
    {
        $this->qb = $qb;
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);
        $this->rootEntityAlias = $entityAlias;
        $source = new MappingQueryDesigner();
        $source->setEntity($entity);
        $definition['columns'] = $columns;
        $definition['composite_columns'] = $compositeColumns;
        $source->setDefinition(json_encode($definition));
        $this->doConvert($source);
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement()
    {
        foreach ($this->definition['composite_columns'] as $compositeColumn) {
            $columnExpressions = [];
            foreach ($compositeColumn['columns'] as $columnName) {
                $fieldName          = $this->getFieldName($columnName);
                $tableAlias = $this->getTableAliasForColumn($columnName);
                $columnExpressions[] = $this->buildColumnExpression($columnName, $tableAlias, $fieldName);
            }
            if (count($columnExpressions) > 1) {
                $columnExpression = sprintf("CONCAT_WS('%s', %s)", ' ', implode(', ', $columnExpressions));
            } else {
                $columnExpression = current($columnExpressions);
            }
            $this->qb->addSelect(sprintf('%s as %s', $columnExpression, $compositeColumn['alias']));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addTableAliasForRootEntity()
    {
        $joinId                              = self::ROOT_ALIAS_KEY;
        $this->tableAliases[$joinId]         = $this->rootEntityAlias;
        $this->joins[$this->rootEntityAlias] = $joinId;
    }

    /**
     * @inheritdoc
     */
    protected function buildColumnAliasKey($column)
    {
        if (is_string($column)) {
            return $column;
        }

        $result = $column['name'];
        if (is_array($result)) {
            $result = md5(implode(','), $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType,
        $isDistinct = false
    ) {
        //not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition)
    {
        if (self::LEFT_JOIN === $joinType) {
            $this->qb->leftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->qb->innerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement($entityClassName, $tableAlias)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement()
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn($columnAlias)
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        // do nothing, order could not change results
    }

    /**
     * {@inheritdoc}
     */
    protected function beginWhereGroup()
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function endWhereGroup()
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereOperator($operator)
    {
        // do nothing, where is not used
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $filterName,
        array $filterData,
        $functionExpr = null
    ) {
        // do nothing, where is not used
    }
}
