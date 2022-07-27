<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\ImportExport\DataConverter\ContactSyncDataConverter;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Prepares QB with all needed Marketing list items
 */
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
     * @var OwnershipMetadataProviderInterface
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

    /** @var EmailProvider */
    protected $emailProvider;

    public function __construct(
        MarketingListProvider $marketingListProvider,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        ManagerRegistry $registry,
        FieldHelper $fieldHelper,
        ContactExportQBAdapterRegistry $exportQBAdapterRegistry,
        EmailProvider $emailProvider
    ) {
        $this->marketingListProvider = $marketingListProvider;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
        $this->exportQBAdapterRegistry = $exportQBAdapterRegistry;
        $this->emailProvider = $emailProvider;
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
        $marketingList = $addressBook->getMarketingList();
        $savedIsUnion = $marketingList->isUnion();
        // skip union to get actual entities only
        $addressBook->getMarketingList()->setUnion(false);

        $qb = $this->getMarketingListItemQuery($addressBook);
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
        $qb->select($qb->expr()->lower($contactInformationFieldExpr));
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
            ->andWhere($expr->notIn('contact.email', $qb->getDQL()))
            ->andWhere($expr->isNotNull('addressBookContact.marketingListItemId'))
            ->andWhere($expr->isNotNull('contact.originId'));
        if ($addressBook->isCreateEntities()) {
            //if address book allows to create new entities, take only contacts not marked as new entity
            $removedItemsQueryBuilder->andWhere(
                $expr->eq('addressBookContact.newEntity', ':newEntity')
            )->setParameter('newEntity', false);
        }

        if (count($excludedItems) > 0) {
            $excludedItems = array_map(function ($item) {
                return $item['id'];
            }, $excludedItems);
            $removedItemsQueryBuilder->andWhere($expr->notIn('addressBookContact.id', $excludedItems));
        }

        // revert union back to saved value
        $addressBook->getMarketingList()->setUnion($savedIsUnion);

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
     * @param AddressBook $addressBook
     * @return QueryBuilder
     */
    public function getFindEntityEmailsQB(AddressBook $addressBook)
    {
        $marketingList = $addressBook->getMarketingList();
        $emailField = $this->emailProvider->getEntityEmailField($marketingList->getEntity());
        if (!$emailField) {
            throw new RuntimeException('Email field cannot be identified');
        }

        $qb = $this->marketingListProvider->getMarketingListEntitiesQueryBuilder(
            $marketingList,
            MarketingListProvider::FULL_ENTITIES_MIXIN
        );
        $from = $qb->getDQLPart('from');
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);
        $qb->resetDQLParts();
        $qb->add('from', $from);
        if (is_array($emailField)) {
            $qb->leftJoin(sprintf('%s.%s', $entityAlias, $emailField['entityEmailField']), 'entityEmail');
            $fieldExp = sprintf('LOWER(entityEmail.%s)', $emailField['emailField']);
            $qb->addSelect($fieldExp);
        } else {
            $fieldExp = sprintf('LOWER(%s.%s)', $entityAlias, $emailField);
            $qb->addSelect($fieldExp);
        }
        $qb->andWhere($qb->expr()->isNotNull($fieldExp));
        $this->applyOrganizationRestrictions($addressBook, $qb);

        return $qb;
    }

    /**
     * @param int $addressBookId
     * @return AddressBook
     */
    public function getAddressBook($addressBookId)
    {
        if (!filter_var($addressBookId, FILTER_VALIDATE_INT) || !$addressBookId) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid $addressBookId, integer expected, "%s" given',
                    is_object($addressBookId) ? get_class($addressBookId) : gettype($addressBookId)
                )
            );
        }

        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(AddressBook::class);

        /** @var AddressBook $addressBook */
        $addressBook = $em->getReference(AddressBook::class, $addressBookId);

        return $addressBook;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return QueryBuilder
     */
    protected function getMarketingListItemQuery(AddressBook $addressBook)
    {
        $marketingList = $addressBook->getMarketingList();
        $qb = $this->marketingListProvider->getMarketingListEntitiesQueryBuilder($marketingList);
        $expr = $qb->expr();
        $qb->resetDQLPart('select');

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

        $qb->addSelect($expr->lower($contactInformationFieldExpr) . ' AS ' . ContactSyncDataConverter::EMAIL_FIELD);
        $joinContactsExpr->add(
            $expr->eq(
                $expr->lower($contactInformationFieldExpr),
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
