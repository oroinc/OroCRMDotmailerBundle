<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter\ContactSyncDataConverter;

class ContactExportQBAdapter implements ContactExportQBAdapterInterface
{
    /**
     * @var DQLNameFormatter
     */
    protected $formatter;

    /**
     * @param DQLNameFormatter $formatter
     */
    public function __construct(DQLNameFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQueryBuilder(QueryBuilder $qb, AddressBook $addressBook)
    {
        $this->addContactInformationFields($qb, $addressBook);
        $this->applyRestrictions($qb);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param AddressBook  $addressBook
     */
    protected function addContactInformationFields(QueryBuilder $qb, AddressBook $addressBook)
    {
        $rootAliases = $qb->getRootAliases();
        $entityAlias = reset($rootAliases);

        $parts = $this->formatter
            ->extractNamePartsPaths(
                $addressBook->getMarketingList()->getEntity(),
                $entityAlias
            );

        if (isset($parts['first_name'])) {
            $qb->addSelect(
                sprintf(
                    '%s AS %s',
                    $parts['first_name'],
                    ContactSyncDataConverter::FIRST_NAME_FIELD
                )
            );
        }
        if (isset($parts['last_name'])) {
            $qb->addSelect(
                sprintf(
                    '%s AS %s',
                    $parts['last_name'],
                    ContactSyncDataConverter::LAST_NAME_FIELD
                )
            );
        }
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function applyRestrictions(QueryBuilder $qb)
    {
        $expr = $qb->expr();
        $syncItemsRestrictions = $expr->isNull(
            MarketingListItemsQueryBuilderProvider::ADDRESS_BOOK_CONTACT_ALIAS.'.id'
        );
        $qb->andWhere($syncItemsRestrictions);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(AddressBook $addressBook)
    {
        return true;
    }
}
