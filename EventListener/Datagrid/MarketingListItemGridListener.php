<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

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

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Model\MarketingListHelper;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class MarketingListItemGridListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ContactInformationFieldsProvider */
    protected $contactInformationProvider;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var MarketingListHelper */
    protected $marketingListHelper;

    /** @var array */
    protected $addressBookByML = [];

    /**
     * @param ManagerRegistry                  $registry
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param FieldHelper                      $fieldHelper
     * @param MarketingListHelper              $marketingListHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        FieldHelper $fieldHelper,
        MarketingListHelper $marketingListHelper
    ) {
        $this->registry = $registry;
        $this->contactInformationProvider = $contactInformationFieldsProvider;
        $this->fieldHelper = $fieldHelper;
        $this->marketingListHelper = $marketingListHelper;
    }

    /**
     * @param BuildAfter $event
     */
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
            $this->addressBookByML[$marketingList->getId()] = $this->registry->getManager()
                ->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->findOneBy(['marketingList' => $marketingList]);
        }

        $isLinkedToAddressBook = !empty($this->addressBookByML[$marketingList->getId()]);
        if ($isLinkedToAddressBook) {
            $config = $datagrid->getConfig();
            $this->removeColumn($config, 'contactedTimes');

            $mixin = $datagrid->getParameters()->get(MixinListener::GRID_MIXIN);
            if ($mixin === 'orocrm-marketing-list-items-mixin') {
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
    protected function getMarketingListFromDatasource(DatasourceInterface $datasource)
    {
        $marketingList = null;
        if ($datasource instanceof OrmDatasource) {
            $mlParameter = $datasource->getQueryBuilder()->getParameter('marketingListEntity');
            $marketingList = $mlParameter ? $mlParameter->getValue() : null;
        }

        return $marketingList;
    }

    /**
     * Accept orocrm_marketing_list_items_grid_* grids only in case when they has mixin to apply.
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

        return (bool)$this->marketingListHelper->getMarketingListIdByGridName($gridName);
    }

    /**
     * Join real subscriber status
     *
     * @param MarketingList $marketingList
     * @param QueryBuilder  $queryBuilder
     */
    protected function joinSubscriberStatus(MarketingList $marketingList, QueryBuilder $queryBuilder)
    {
        $contactInformationFields = $this->contactInformationProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        if (!$contactInformationField = reset($contactInformationFields)) {
            throw new \RuntimeException('Contact information is not provided');
        }


        $contactInformationFieldExpr = $this->fieldHelper
            ->getFieldExpr($marketingList->getEntity(), $queryBuilder, $contactInformationField);
        $queryBuilder->addSelect($contactInformationFieldExpr . ' AS entityEmail');

        $expr = $queryBuilder->expr();
        $joinContactsExpr = $expr->andX()
            ->add(
                $expr->eq(
                    $contactInformationFieldExpr,
                    'dm_contact_subscriber.email'
                )
            );

        $joinContactsExpr->add('dm_contact_subscriber.channel =:channel');

        $queryBuilder->leftJoin(
            'OroCRM\Bundle\DotmailerBundle\Entity\Contact',
            'dm_contact_subscriber',
            Join::WITH,
            $joinContactsExpr
        );
        /** @var AddressBook $addressBook */
        $addressBook = $this->addressBookByML[$marketingList->getId()];
        $queryBuilder->leftJoin(
            'OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact',
            'dm_ab_contact',
            Join::WITH,
            'IDENTITY(dm_ab_contact.contact) = dm_contact_subscriber.id AND dm_ab_contact.addressBook = :aBookFilter'
        )
            ->setParameter('aBookFilter', $addressBook)
            ->setParameter('channel', $addressBook->getChannel())
            ->addSelect('IDENTITY(dm_ab_contact.status) as addressBookSubscribedStatus');
    }

    /**
     * @param DatagridInterface $datagrid
     */
    protected function rewriteActionConfiguration(DatagridInterface $datagrid)
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
        $wasUnsubscribed = false === in_array(
            $subscriberStatus,
            [Contact::STATUS_SUBSCRIBED, Contact::STATUS_SOFTBOUNCED]
        );

        $permissions['subscribe'] = !$isSubscribed && (!$syncedWithDotmailer || !$wasUnsubscribed);
        $permissions['unsubscribe'] = $isSubscribed;

        return $permissions;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $fieldName
     */
    protected function removeColumn(DatagridConfiguration $config, $fieldName)
    {
        $config->offsetUnsetByPath(sprintf('[columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $fieldName));
    }
}
