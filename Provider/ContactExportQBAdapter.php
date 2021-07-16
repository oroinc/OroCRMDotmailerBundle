<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\QueryDesigner\MappingQueryConverter;

class ContactExportQBAdapter implements ContactExportQBAdapterInterface
{
    /** @var MappingProvider */
    protected $mappingProvider;

    /** @var MappingQueryConverter */
    protected $mappingQueryConverter;

    public function __construct(MappingProvider $mappingProvider, MappingQueryConverter $mappingQueryConverter)
    {
        $this->mappingProvider = $mappingProvider;
        $this->mappingQueryConverter = $mappingQueryConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQueryBuilder(QueryBuilder $qb, AddressBook $addressBook)
    {
        $this->addMappedFields($qb, $addressBook);
        $this->applyRestrictions($qb, $addressBook);

        return $qb;
    }

    protected function addMappedFields(QueryBuilder $qb, AddressBook $addressBook)
    {
        $entity = $addressBook->getMarketingList()->getEntity();
        $mapping = $this->mappingProvider->getExportMappingConfigForEntity(
            $entity,
            $addressBook->getChannel()->getId()
        );

        if ($mapping) {
            $columns = [];
            $compositeColumns = [];
            foreach ($mapping as $dataField => $entityFields) {
                $entityFields = explode(',', $entityFields);
                $compositeColumn = [
                    'columns' => [],
                    'alias' => $dataField
                ];
                foreach ($entityFields as $entityField) {
                    $columns[] = ['name' => $entityField];
                    $compositeColumn['columns'][] = $entityField;
                }
                $compositeColumns[] = $compositeColumn;
            }
            $this->mappingQueryConverter->addMappingColumns($qb, $entity, $columns, $compositeColumns);
        }
    }

    protected function applyRestrictions(QueryBuilder $qb, AddressBook $addressBook)
    {
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);

        $expr = $qb->expr();
        $syncItemsRestrictions = $expr->orX();
        $syncItemsRestrictions->add(
            $expr->isNull(
                MarketingListItemsQueryBuilderProvider::ADDRESS_BOOK_CONTACT_ALIAS . '.id'
            )
        );
        $marketingListItemExpression = MarketingListItemsQueryBuilderProvider::ADDRESS_BOOK_CONTACT_ALIAS
            . '.marketingListItemId';
        $syncItemsRestrictions->add($expr->isNull($marketingListItemExpression));
        $syncItemsRestrictions->add(
            $expr->neq(
                $marketingListItemExpression,
                "$entityAlias.id"
            )
        );
        $marketingListItemClassExpression = MarketingListItemsQueryBuilderProvider::ADDRESS_BOOK_CONTACT_ALIAS
            . '.marketingListItemClass';
        $syncItemsRestrictions->add($expr->isNull($marketingListItemClassExpression));
        $syncItemsRestrictions->add(
            $expr->neq(
                $marketingListItemClassExpression,
                ':entityClass'
            )
        );
        $qb->andWhere($syncItemsRestrictions)
            ->setParameter('entityClass', $addressBook->getMarketingList()->getEntity());
        //include contacts which have recently updated related entities
        $entityUpdateFieldExpression = MarketingListItemsQueryBuilderProvider::ADDRESS_BOOK_CONTACT_ALIAS
            . '.entityUpdated';
        $qb->orWhere($expr->eq($entityUpdateFieldExpression, ':isUpdated'))->setParameter('isUpdated', true);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(AddressBook $addressBook)
    {
        return true;
    }
}
