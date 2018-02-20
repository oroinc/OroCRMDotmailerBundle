<?php

namespace Oro\Bundle\DotmailerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\AbstractOrmQueryConverter;

class ParentEntityFindQueryConverter extends AbstractOrmQueryConverter
{
    const PARENT_ENTITY_ID_ALIAS = 'entityId';

    /** @var QueryBuilder */
    protected $qb;

    /** @var string */
    protected $rootEntityAlias;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper($doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $entity
     * @param array $columns
     * @return QueryBuilder
     */
    public function convert($entity, $columns)
    {
        $this->qb = $this->doctrine->getManagerForClass($entity)->createQueryBuilder();
        $source = new MappingQueryDesigner();
        $source->setEntity($entity);
        $definition['columns'] = $columns;
        $source->setDefinition(json_encode($definition));
        $this->doConvert($source);

        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectStatement()
    {
        //need to know parent entity id only
        $idField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->getRootEntity());
        $tableAlias = $this->getTableAliasForColumn(self::ROOT_ALIAS_KEY);
        $this->qb->addSelect(sprintf('%s.%s as %s', $tableAlias, $idField, self::PARENT_ENTITY_ID_ALIAS));
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
        $this->qb->from($entityClassName, $tableAlias);
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            /**
             * add condition to find parent entity id by modified entity id
             */
            $columnName = $column['name'];
            $value = $column['value'];
            $relatedEntity = $this->getEntityClassName($columnName);
            $relatedFieldName = $this->getFieldName($columnName);
            $idFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($relatedEntity);
            $tableAlias = $this->getTableAliasForColumn($columnName);
            $columnExpression = $this->buildColumnExpression($columnName, $tableAlias, $idFieldName);
            //in case of virtual relations, field name still should be replaced with id field name
            $columnExpression = str_replace($relatedFieldName, $idFieldName, $columnExpression);
            $this->qb->andWhere(sprintf("%s = :value", $columnExpression))->setParameter('value', $value);
        }
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
