<?php

namespace Oro\Bundle\DotmailerBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Utils\EmailUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Abstract class that return query builder based on marketing list
 */
abstract class AbstractMarketingListEntitiesAction extends AbstractAction
{
    const MARKETING_LIST_ENTITY_QB_ALIAS = 'marketingListEntity';

    const CACHE_SCOPE = 'ml_contact_fields';

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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

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
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper($doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param MarketingList $marketingList
     * @param string $email
     * @return BufferedQueryResultIteratorInterface
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

        return new BufferedIdentityQueryResultIterator($query);
    }

    /**
     * @param MarketingList $marketingList
     * @param string $email
     * @return QueryBuilder
     */
    protected function getMarketingListEntitiesByEmailQueryBuilder(MarketingList $marketingList, $email)
    {
        $emailFields = $this->cacheProvider->getCachedItem(self::CACHE_SCOPE, $marketingList->getName());
        if (!$emailFields) {
            $emailFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
                $marketingList,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            );
            $this->cacheProvider->setCachedItem(self::CACHE_SCOPE, $marketingList->getName(), $emailFields);
        }
        $qb = $this->getEntitiesQueryBuilder($marketingList);

        $expr = $qb->expr()->orX();
        foreach ($emailFields as $emailField) {
            $parameterName = $emailField . mt_rand();
            QueryBuilderUtil::checkIdentifier($parameterName);
            $expr->add(
                $qb->expr()->eq(
                    $qb->expr()->lower($this->fieldHelper->getFieldExpr($marketingList->getEntity(), $qb, $emailField)),
                    ':' . $parameterName
                )
            );
            $qb->setParameter($parameterName, EmailUtils::getLowerCaseEmail($email));
        }
        $qb->andWhere($expr);

        return $qb;
    }

    /**
     * @param MarketingList $marketingList
     * @return QueryBuilder
     */
    protected function getEntitiesQueryBuilder(MarketingList $marketingList)
    {
        return $this->doctrineHelper
            ->getEntityRepository($marketingList->getEntity())
            ->createQueryBuilder(self::MARKETING_LIST_ENTITY_QB_ALIAS);
    }
}
