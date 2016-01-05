<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter\ContactSyncDataConverter;
use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use OroCRM\Bundle\MarketingListBundle\Provider\MarketingListProvider;

class MarketingListItemsQueryBuilderProvider
{
    const CONTACT_ALIAS = 'dm_contact';
    const MARKETING_LIST_ITEM_ID = 'marketingListItemId';
    const ADDRESS_BOOK_CONTACT_ALIAS = 'addressBookContacts';

    /**
     * @var MarketingListProvider
     */
    protected $marketingListProvider;

    /**
     * @var ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadataProvider;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $removedItemClassName;

    /**
     * @var string
     */
    protected $unsubscribedItemClassName;

    /**
     * @var string
     */
    protected $contactClassName;
    /**
     * @var string
     */
    protected $addressBookContactClassName;

    /**
     * @var ContactExportQBAdapterRegistry
     */
    protected $exportQBAdapterRegistry;

    /**
     * @var QueryBuilder[]
     */
    protected $cachedQueryBuilders = [];

    /**
     * @param MarketingListProvider            $marketingListProvider
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param OwnershipMetadataProvider        $ownershipMetadataProvider
     * @param ManagerRegistry                  $registry
     * @param FieldHelper                      $fieldHelper
     * @param ContactExportQBAdapterRegistry   $exportQBAdapterRegistry
     */
    public function __construct(
        MarketingListProvider $marketingListProvider,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        ManagerRegistry $registry,
        FieldHelper $fieldHelper,
        ContactExportQBAdapterRegistry $exportQBAdapterRegistry
    ) {
        $this->marketingListProvider = $marketingListProvider;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
        $this->exportQBAdapterRegistry = $exportQBAdapterRegistry;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @param array       $excludedItems
     *
     * @return QueryBuilder
     */
    public function getMarketingListItemsQB(AddressBook $addressBook, array $excludedItems)
    {
        $qb = $this->getMarketingListItemQuery($addressBook);
        $expr = $qb->expr();
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);
        $qb->addSelect("$entityAlias.id as " . self::MARKETING_LIST_ITEM_ID);

        /**
         * Get create or update marketing list items query builder
         */
        $qb = $this->exportQBAdapterRegistry
            ->getAdapterByAddressBook($addressBook)
            ->prepareQueryBuilder($qb, $addressBook);

        $qb->leftJoin(
            sprintf('%s.addressBookContacts', self::CONTACT_ALIAS),
            self::ADDRESS_BOOK_CONTACT_ALIAS,
            Join::WITH,
            self::ADDRESS_BOOK_CONTACT_ALIAS . '.addressBook =:addressBook'
        )->setParameter('addressBook', $addressBook);
        $qb->andWhere(
            $expr->orX()
                ->add($expr->isNull(self::ADDRESS_BOOK_CONTACT_ALIAS . '.id'))
                ->add(self::ADDRESS_BOOK_CONTACT_ALIAS . '.scheduledForExport <> TRUE')
        );
        if (count($excludedItems) > 0) {
            $excludedItems = array_map(function ($item) {
                return $item[self::MARKETING_LIST_ITEM_ID];
            }, $excludedItems);
            $qb->andWhere($expr->notIn("$entityAlias.id", $excludedItems));
        }

        /**
         * Get only subscribed to address book contacts because
         * of other type of address book contacts is already removed from address book.
         */
        $qb->leftJoin(sprintf('%s.status', self::ADDRESS_BOOK_CONTACT_ALIAS), 'addressBookContactStatus')
            ->andWhere(
                $expr->orX()
                    ->add($expr->isNull('addressBookContactStatus.id'))
                    ->add($expr->in(
                        'addressBookContactStatus.id',
                        [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED]
                    ))
            );

        return $qb;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @param array       $excludedItems
     *
     * @return QueryBuilder
     */
    public function getRemovedMarketingListItemsQB(AddressBook $addressBook, array $excludedItems)
    {
        $qb = $this->getMarketingListItemQuery($addressBook);
        $marketingList = $addressBook->getMarketingList();
        $contactInformationFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        if (!$contactInformationField = reset($contactInformationFields)) {
            throw new RuntimeException('Contact information is not provided');
        }
        $contactInformationFieldExpr = $this->fieldHelper
            ->getFieldExpr($marketingList->getEntity(), $qb, $contactInformationField);

        /**
         * Distinct used in select leads to exception in postgresql
         * in case if order by field not presented in select
         */
        $qb->select($contactInformationFieldExpr);
        $qb->resetDQLPart('orderBy');

        $removedItemsQueryBuilder = clone $qb;
        $expr = $removedItemsQueryBuilder->expr();
        $removedItemsQueryBuilder
            ->resetDQLParts()
            ->select('addressBookContact.id')
            ->addSelect('contact.originId')
            ->from($this->addressBookContactClassName, 'addressBookContact')
            ->innerJoin('addressBookContact.contact', 'contact')
            ->leftJoin('addressBookContact.status', 'status')
            ->where('addressBookContact.addressBook =:addressBook')
            ->setParameter('addressBook', $addressBook)
            /**
             * Get only subscribed to address book contacts because
             * of other type of address book contacts is already removed from address book.
             */
            ->andWhere(
                $qb->expr()
                    ->in(
                        'status.id',
                        [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED]
                    )
            )
            /**
             * Select only Address book contacts for which marketing list items not exist
             */
            ->andWhere(
                $expr->notIn('contact.email', $qb->getDQL())
            )->andWhere(
                $removedItemsQueryBuilder->expr()->isNotNull('addressBookContact.marketingListItemId')
            );

        if (count($excludedItems) > 0) {
            $excludedItems = array_map(function ($item) {
                return $item['id'];
            }, $excludedItems);
            $removedItemsQueryBuilder->andWhere($expr->notIn('addressBookContact.id', $excludedItems));
        }

        return $removedItemsQueryBuilder;
    }

    /**
     * @param AddressBook $addressBook
     * @param array       $excludedItems
     *
     * @return QueryBuilder
     */
    public function getOutOfSyncMarketingListItemsQB(AddressBook $addressBook, array $excludedItems)
    {
        $qb = $this->getMarketingListItemQuery($addressBook);
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);

        $qb->innerJoin(
            sprintf('%s.addressBookContacts', self::CONTACT_ALIAS),
            self::ADDRESS_BOOK_CONTACT_ALIAS,
            Join::WITH,
            self::ADDRESS_BOOK_CONTACT_ALIAS . '.addressBook =:addressBook'
        )->setParameter('addressBook', $addressBook);

        $expr = $qb->expr();

        /**
         * Get only not subscribed to address book contacts for status synchronization
         */
        $qb->innerJoin(sprintf('%s.status', self::ADDRESS_BOOK_CONTACT_ALIAS), 'addressBookContactStatus')
            ->andWhere(
                $expr->orX()
                    ->add($expr->isNull('addressBookContactStatus.id'))
                    ->add(
                        $expr->notIn(
                            'addressBookContactStatus.id',
                            [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED]
                        )
                    )
            );

        if (count($excludedItems) > 0) {
            $excludedItems = array_map(function ($item) {
                return $item[self::MARKETING_LIST_ITEM_ID];
            }, $excludedItems);
            $qb->andWhere($expr->notIn("$entityAlias.id", $excludedItems));
        }

        $qb->select("$entityAlias.id as " . self::MARKETING_LIST_ITEM_ID);

        return $qb;
    }

    /**
     * @param MarketingList $marketingList
     *
     * @return QueryBuilder
     */
    public function getCachedMarketingListEntitiesQB(MarketingList $marketingList)
    {
        if (count($this->cachedQueryBuilders) > 100) {
            $this->cachedQueryBuilders = [];
        }

        if (!isset($this->cachedQueryBuilders[$marketingList->getId()])) {
            $this->cachedQueryBuilders[$marketingList->getId()] = $this->marketingListProvider
                ->getMarketingListEntitiesQueryBuilder($marketingList, MarketingListProvider::FULL_ENTITIES_MIXIN)
                /**
                 * In some cases Marketing list segment can contain duplicate records because of
                 * join to many entities. Duplicate records should not be processed during import
                 */
                ->distinct(true);
        }

        return clone $this->cachedQueryBuilders[$marketingList->getId()];
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getMarketingListItemQuery(AddressBook $addressBook)
    {
        $marketingList = $addressBook->getMarketingList();
        $qb = $this->marketingListProvider->getMarketingListEntitiesQueryBuilder(
            $marketingList,
            MarketingListProvider::FULL_ENTITIES_MIXIN
        );
        $expr = $qb->expr();
        $qb->resetDQLPart('select');
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);
        $qb
            ->leftJoin(
                $this->removedItemClassName,
                'mlr',
                Join::WITH,
                "mlr.entityId = $entityAlias.id and mlr.marketingList =:marketingList"
            )
            ->andWhere($expr->isNull('mlr.id'))
            ->leftJoin(
                $this->unsubscribedItemClassName,
                'mlu',
                Join::WITH,
                "mlu.entityId = $entityAlias.id and mlu.marketingList =:marketingList"
            )
            ->andWhere($expr->isNull('mlu.id'))
            ->setParameter('marketingList', $marketingList);

        $contactInformationFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        if (!$contactInformationField = reset($contactInformationFields)) {
            throw new RuntimeException('Contact information is not provided');
        }

        $joinContactsExpr = $expr->andX();
        $contactInformationFieldExpr = $this->fieldHelper
            ->getFieldExpr($marketingList->getEntity(), $qb, $contactInformationField);

        $qb->addSelect($contactInformationFieldExpr . ' AS ' . ContactSyncDataConverter::EMAIL_FIELD);
        $joinContactsExpr->add(
            $expr->eq(
                $contactInformationFieldExpr,
                sprintf('%s.email', self::CONTACT_ALIAS)
            )
        );
        $joinContactsExpr->add(
            self::CONTACT_ALIAS . '.channel =:channel'
        );
        $qb->andWhere("$contactInformationFieldExpr <> ''");
        $qb->andWhere($expr->isNotNull($contactInformationFieldExpr));
        $this->applyOrganizationRestrictions($addressBook, $qb);
        $qb->leftJoin(
            $this->contactClassName,
            self::CONTACT_ALIAS,
            Join::WITH,
            $joinContactsExpr
        )->setParameter('channel', $addressBook->getChannel());

        /**
         * In some cases Marketing list segment can contain duplicate records because of
         * join to many entities. Duplicate records should not be processed during import
         */
        $qb->distinct(true);

        return $qb;
    }

    /**
     * @param AddressBook  $addressBook
     * @param QueryBuilder $qb
     */
    protected function applyOrganizationRestrictions(AddressBook $addressBook, QueryBuilder $qb)
    {
        $organization = $addressBook->getOwner();
        $metadata = $this->ownershipMetadataProvider->getMetadata($addressBook->getMarketingList()->getEntity());

        if ($organization && $fieldName = $metadata->getOrganizationFieldName()) {
            $aliases = $qb->getRootAliases();
            $qb->andWhere(
                $qb->expr()->eq(
                    sprintf('%s.%s', reset($aliases), $fieldName),
                    ':organization'
                )
            );

            $qb->setParameter('organization', $organization);
        }
    }

    /**
     * @param string $removedItemClassName
     *
     * @return MarketingListItemsQueryBuilderProvider
     */
    public function setRemovedItemClassName($removedItemClassName)
    {
        $this->removedItemClassName = $removedItemClassName;

        return $this;
    }

    /**
     * @param string $unsubscribedItemClassName
     *
     * @return MarketingListItemsQueryBuilderProvider
     */
    public function setUnsubscribedItemClassName($unsubscribedItemClassName)
    {
        $this->unsubscribedItemClassName = $unsubscribedItemClassName;

        return $this;
    }

    /**
     * @param string $contactClassName
     *
     * @return MarketingListItemsQueryBuilderProvider
     */
    public function setContactClassName($contactClassName)
    {
        $this->contactClassName = $contactClassName;

        return $this;
    }

    /**
     * @param string $addressBookContactClassName
     *
     * @return MarketingListItemsQueryBuilderProvider
     */
    public function setAddressBookContactClassName($addressBookContactClassName)
    {
        $this->addressBookContactClassName = $addressBookContactClassName;

        return $this;
    }
}
