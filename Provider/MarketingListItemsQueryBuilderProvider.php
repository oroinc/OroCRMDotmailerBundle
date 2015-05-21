<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use OroCRM\Bundle\MarketingListBundle\Provider\MarketingListProvider;

class MarketingListItemsQueryBuilderProvider
{
    const CONTACT_ALIAS = 'dm_contact';
    const CONTACT_EMAIL_FIELD = 'email';
    const CONTACT_FIRS_NAME_FIELD = 'firsName';
    const CONTACT_LAST_NAME_FIELD = 'lastName';
    const MARKETING_LIST_ITEM_ID = 'marketingListItemId';
    /**
     * @var MarketingListProvider
     */
    protected $marketingListProvider;

    /**
     * @var DQLNameFormatter
     */
    protected $formatter;

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
     * @param MarketingListProvider            $marketingListProvider
     * @param DQLNameFormatter                 $formatter
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param OwnershipMetadataProvider        $ownershipMetadataProvider
     * @param ManagerRegistry                  $registry
     * @param FieldHelper                      $fieldHelper
     */
    public function __construct(
        MarketingListProvider $marketingListProvider,
        DQLNameFormatter $formatter,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        ManagerRegistry $registry,
        FieldHelper $fieldHelper
    ) {
        $this->marketingListProvider = $marketingListProvider;
        $this->formatter = $formatter;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->registry = $registry;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param AddressBook $addressBook
     *
     * @throws \InvalidArgumentException
     * @return QueryBuilder
     */
    public function getMarketingListItemsQB(AddressBook $addressBook)
    {
        $marketingList = $addressBook->getMarketingList();
        $qb = $this->marketingListProvider->getMarketingListEntitiesQueryBuilder(
            $marketingList,
            MarketingListProvider::FULL_ENTITIES_MIXIN
        );

        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);

        $qb
            ->leftJoin(
                $this->removedItemClassName,
                'mlr',
                Join::WITH,
                "mlr.entityId = $entityAlias.id"
            )
            ->andWhere($qb->expr()->isNull('mlr.id'))
            ->leftJoin(
                $this->unsubscribedItemClassName,
                'mlu',
                Join::WITH,
                "mlu.entityId = $entityAlias.id"
            )
            ->andWhere($qb->expr()->isNull('mlu.id'));
        $parts = $this->formatter->extractNamePartsPaths($marketingList->getEntity(), $entityAlias);

        $qb->resetDQLPart('select');
        $qb->addSelect("$entityAlias.id as ". self::MARKETING_LIST_ITEM_ID);
        $expr = $qb->expr();
        $isItemModifiedPart = $expr->orX();
        if (isset($parts['first_name'])) {
            $qb->addSelect(sprintf('%s AS %s', $parts['first_name'], self::CONTACT_FIRS_NAME_FIELD));
            $isItemModifiedPart->add(
                $expr->neq(
                    $parts['first_name'],
                    sprintf('%s.firstName', self::CONTACT_ALIAS)
                )
            );
        }
        if (isset($parts['last_name'])) {
            $qb->addSelect(sprintf('%s AS %s', $parts['last_name'], self::CONTACT_LAST_NAME_FIELD));
            $isItemModifiedPart->add(
                $expr->neq(
                    $parts['last_name'],
                    sprintf('%s.lastName', self::CONTACT_ALIAS)
                )
            );
        }
        $this->prepareMarketingListItemQuery($addressBook, $qb);
        $qb->leftJoin(
            sprintf('%s.addressBookContacts', self::CONTACT_ALIAS),
            'addressBookContacts',
            Join::WITH,
            'addressBookContacts.addressBook =:addressBook'
        )->setParameter('addressBook', $addressBook);

        $qb->andWhere(
            $expr->orX()
                ->add(sprintf('%s.id is NULL', self::CONTACT_ALIAS))
                ->add($isItemModifiedPart)
        );
        /**
         * Get only subscribed to address book contacts because
         * of other type of address book contacts is already removed from address book.
         */
        $qb->leftJoin('addressBookContacts.status', 'addressBookContactStatus')
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
     * @return QueryBuilder
     */
    public function getRemovedMarketingListItemsQB(AddressBook $addressBook)
    {
        $marketingList = $addressBook->getMarketingList();
        $qb = $this->marketingListProvider->getMarketingListEntitiesQueryBuilder(
            $marketingList,
            MarketingListProvider::FULL_ENTITIES_MIXIN
        );

        $this->prepareMarketingListItemQuery($addressBook, $qb);
        $aliases = $qb->getRootAliases();
        $qb->select(sprintf('%s.id', reset($aliases)));
        $removedItemsQueryBuilder = clone $qb;
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
                $removedItemsQueryBuilder->expr()
                    ->notIn('addressBookContact.marketingListItemId', $qb->getDQL())
            )->andWhere(
                $removedItemsQueryBuilder->expr()->isNotNull('addressBookContact.marketingListItemId')
            );

        return $removedItemsQueryBuilder;
    }

    /**
     * @param AddressBook  $addressBook
     * @param QueryBuilder $qb
     */
    protected function prepareMarketingListItemQuery(AddressBook $addressBook, QueryBuilder $qb)
    {
        $marketingList = $addressBook->getMarketingList();

        $contactInformationFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        $expr = $qb->expr()->orX();
        foreach ($contactInformationFields as $contactInformationField) {
            $contactInformationFieldExpr = $this->fieldHelper
                ->getFieldExpr($marketingList->getEntity(), $qb, $contactInformationField);

            $qb->addSelect($contactInformationFieldExpr . ' AS ' . self::CONTACT_EMAIL_FIELD);
            $expr->add(
                $qb->expr()->eq(
                    $contactInformationFieldExpr,
                    sprintf('%s.email', self::CONTACT_ALIAS)
                )
            );
        }

        $this->applyOrganizationRestrictions($addressBook, $qb);
        $qb->leftJoin(
            $this->contactClassName,
            self::CONTACT_ALIAS,
            Join::WITH,
            $expr
        );
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
