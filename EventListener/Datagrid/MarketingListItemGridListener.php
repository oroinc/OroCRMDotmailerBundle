<?php

namespace Oro\Bundle\DotmailerBundle\EventListener\Datagrid;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Model\MarketingListHelper;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Join real subscriber status on the marketing list data grid
 */
class MarketingListItemGridListener implements ServiceSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ContainerInterface */
    private $container;

    /** @var array */
    private $addressBookByML = [];

    public function __construct(
        ManagerRegistry $doctrine,
        ContainerInterface $container
    ) {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_marketing_list.provider.contact_information_fields' => ContactInformationFieldsProvider::class,
            'oro_marketing_list.model.helper'                        => MarketingListHelper::class,
            'oro_dotmailer.model.field_helper'                       => FieldHelper::class
        ];
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        if (!$this->isApplicable($datagrid->getName(), $datagrid->getParameters())) {
            return;
        }

        /** @var OrmDatasource $datasource */
        $datasource = $datagrid->getDatasource();
        $marketingList = $this->getMarketingListFromDatasource($datasource);

        $isMarketingList = $marketingList instanceof MarketingList;
        if (!$isMarketingList) {
            return;
        }

        if (empty($this->addressBookByML[$marketingList->getId()])) {
            $this->addressBookByML[$marketingList->getId()] = $this->doctrine->getManager()
                ->getRepository(AddressBook::class)
                ->findOneBy(['marketingList' => $marketingList]);
        }

        $isLinkedToAddressBook = !empty($this->addressBookByML[$marketingList->getId()]);
        if ($isLinkedToAddressBook) {
            $config = $datagrid->getConfig();
            $this->removeColumn($config, 'contactedTimes');

            $mixin = $datagrid->getParameters()->get(MixinListener::GRID_MIXIN);
            if ($mixin === 'oro-marketing-list-items-mixin') {
                $this->joinSubscriberStatus($marketingList, $datasource->getQueryBuilder());
                $this->rewriteActionConfiguration($datagrid);
            }
        }
    }

    /**
     * @param DatasourceInterface $datasource
     *
     * @return MarketingList|null
     */
    private function getMarketingListFromDatasource(DatasourceInterface $datasource)
    {
        $marketingList = null;
        if ($datasource instanceof OrmDatasource) {
            $mlParameter = $datasource->getQueryBuilder()->getParameter('marketingListEntity');
            $marketingList = $mlParameter ? $mlParameter->getValue() : null;
        }

        return $marketingList;
    }

    /**
     * Accept oro_marketing_list_items_grid_* grids only in case when they has mixin to apply.
     *
     * @param string       $gridName
     * @param ParameterBag $parameters
     *
     * @return bool
     */
    public function isApplicable($gridName, $parameters)
    {
        if (!$parameters->get(MixinListener::GRID_MIXIN, false)) {
            return false;
        }

        /** @var MarketingListHelper $marketingListHelper */
        $marketingListHelper = $this->container->get('oro_marketing_list.model.helper');

        return (bool)$marketingListHelper->getMarketingListIdByGridName($gridName);
    }

    /**
     * Join real subscriber status
     */
    private function joinSubscriberStatus(MarketingList $marketingList, QueryBuilder $queryBuilder)
    {
        /** @var ContactInformationFieldsProvider $contactInformationProvider */
        $contactInformationProvider = $this->container->get('oro_marketing_list.provider.contact_information_fields');
        $contactInformationFields = $contactInformationProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        if (!$contactInformationField = reset($contactInformationFields)) {
            throw new \RuntimeException('Contact information is not provided');
        }

        $expr = $queryBuilder->expr();

        /** @var FieldHelper $fieldHelper */
        $fieldHelper = $this->container->get('oro_dotmailer.model.field_helper');
        $contactInformationFieldExpr = $fieldHelper
            ->getFieldExpr($marketingList->getEntity(), $queryBuilder, $contactInformationField);
        $queryBuilder->addSelect($expr->lower($contactInformationFieldExpr) . ' AS entityEmail');

        $joinContactsExpr = $expr->andX()
            ->add(
                $expr->eq(
                    $expr->lower($contactInformationFieldExpr),
                    'dm_contact_subscriber.email'
                )
            );

        $joinContactsExpr->add('dm_contact_subscriber.channel =:channel');

        $queryBuilder->leftJoin(
            Contact::class,
            'dm_contact_subscriber',
            Join::WITH,
            $joinContactsExpr
        );
        /** @var AddressBook $addressBook */
        $addressBook = $this->addressBookByML[$marketingList->getId()];
        $queryBuilder->leftJoin(
            AddressBookContact::class,
            'dm_ab_contact',
            Join::WITH,
            'IDENTITY(dm_ab_contact.contact) = dm_contact_subscriber.id AND dm_ab_contact.addressBook = :aBookFilter'
        )
            ->setParameter('aBookFilter', $addressBook)
            ->setParameter('channel', $addressBook->getChannel())
            ->addSelect('IDENTITY(dm_ab_contact.status) as addressBookSubscribedStatus');
    }

    private function rewriteActionConfiguration(DatagridInterface $datagrid)
    {
        $config = $datagrid->getConfig();
        $callable = function (ResultRecordInterface $record) use ($config) {
            $result = $this->getMarketingListItemPermissions($record, $config->offsetGetOr('actions', []));

            return is_array($result) ? $result : [];
        };

        $propertyConfig = [
            'type'                               => 'callback',
            'callable'                           => $callable,
            PropertyInterface::FRONTEND_TYPE_KEY => 'array'
        ];

        $config->offsetAddToArrayByPath(
            sprintf(
                '[%s][%s]',
                Configuration::PROPERTIES_KEY,
                ActionExtension::METADATA_ACTION_CONFIGURATION_KEY
            ),
            $propertyConfig
        );
    }

    /**
     * @param ResultRecordInterface $record
     * @param array                 $actions
     *
     * @return array
     */
    public function getMarketingListItemPermissions(ResultRecordInterface $record, array $actions)
    {
        $actions = array_keys($actions);
        $permissions = [];
        foreach ($actions as $action) {
            $permissions[$action] = true;
        }

        $isSubscribed = (bool)$record->getValue('subscribed');

        $subscriberStatus = $record->getValue('addressBookSubscribedStatus');
        $syncedWithDotmailer = $subscriberStatus !== null;

        // treat as unsubscribed all statuses except these
        $wasUnsubscribed = !in_array(
            $subscriberStatus,
            [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED],
            true
        );

        $permissions['subscribe'] = !$isSubscribed && (!$syncedWithDotmailer || !$wasUnsubscribed);
        $permissions['unsubscribe'] = $isSubscribed;

        return $permissions;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $fieldName
     */
    private function removeColumn(DatagridConfiguration $config, $fieldName)
    {
        $config->offsetUnsetByPath(sprintf('[columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $fieldName));
    }
}
