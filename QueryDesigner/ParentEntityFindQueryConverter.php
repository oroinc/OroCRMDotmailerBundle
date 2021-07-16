<?php

namespace Oro\Bundle\DotmailerBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;

/**
 * Converts column definitions to an ORM query.
 */
class ParentEntityFindQueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    public const PARENT_ENTITY_ID_ALIAS = 'entityId';

    public function convert(string $entity, array $columns): QueryBuilder
    {
        $source = new QueryDesigner(
            $entity,
            $this->encodeDefinition(['columns' => $columns])
        );

        $qb = $this->doctrineHelper->getEntityManagerForClass($entity)->createQueryBuilder();
        $this->context()->setQueryBuilder($qb);
        $this->doConvert($source);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectStatement(): void
    {
        $this->context()->getQueryBuilder()->addSelect(sprintf(
            '%s.%s as %s',
            $this->context()->getRootTableAlias(),
            $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->context()->getRootEntity()),
            self::PARENT_ENTITY_ID_ALIAS
        ));
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
    protected function addWhereStatement(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            /**
             * add condition to find parent entity id by modified entity id
             */
            $columnName = $column['name'];
            $value = $column['value'];
            $relatedEntity = $this->getEntityClass($columnName);
            $relatedFieldName = $this->getFieldName($columnName);
            $idFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($relatedEntity);
            $tableAlias = $this->getTableAliasForColumn($columnName);
            $columnExpression = $this->buildColumnExpression($columnName, $tableAlias, $idFieldName);
            //in case of virtual relations, field name still should be replaced with id field name
            $columnExpression = str_replace($relatedFieldName, $idFieldName, $columnExpression);
            $context->getQueryBuilder()
                ->andWhere(sprintf('%s = :value', $columnExpression))
                ->setParameter('value', $value);
        }
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
