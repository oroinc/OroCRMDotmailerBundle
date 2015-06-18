<?php

namespace OroCRM\Bundle\DotmailerBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

abstract class AbstractMarketingListEntitiesAction extends AbstractAction
{
    /**
     * @var ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider,
        FieldHelper $fieldHelper
    ) {
        parent::__construct($contextAccessor);

        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param MarketingList $marketingList
     * @param string $email
     * @return BufferedQueryResultIterator
     */
    protected function getMarketingListEntitiesByEmail(MarketingList $marketingList, $email)
    {
        $query = $this->getMarketingListEntitiesByEmailQueryBuilder($marketingList, $email)
            ->getQuery()
            /**
             * Call multiple times during import and because of it
             * cache grows larger and script getting out of memory.
             */
            ->useQueryCache(false);

        return new BufferedQueryResultIterator($query);
    }

    /**
     * @param MarketingList $marketingList
     * @param string $email
     * @return QueryBuilder
     */
    protected function getMarketingListEntitiesByEmailQueryBuilder(MarketingList $marketingList, $email)
    {
        $emailFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        $qb = $this->getEntitiesQueryBuilder($marketingList);

        $expr = $qb->expr()->orX();
        foreach ($emailFields as $emailField) {
            $parameterName = $emailField . mt_rand();
            $expr->add(
                $qb->expr()->eq(
                    $this->fieldHelper->getFieldExpr($marketingList->getEntity(), $qb, $emailField),
                    ':' . $parameterName
                )
            );
            $qb->setParameter($parameterName, $email);
        }
        $qb->andWhere($expr);

        return $qb;
    }

    /**
     * @param MarketingList $marketingList
     * @return QueryBuilder
     */
    abstract protected function getEntitiesQueryBuilder(MarketingList $marketingList);
}
