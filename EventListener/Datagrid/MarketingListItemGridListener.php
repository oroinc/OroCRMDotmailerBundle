<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

class MarketingListItemGridListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getMarketingListItemPermissions(ResultRecordInterface $record, array $actions)
    {
        $actions     = array_keys($actions);
        $permissions = array();
        foreach ($actions as $action) {
            $permissions[$action] = true;
        }

        $isSubscribed    = (bool)$record->getValue('subscribed');
        $wasUnsubscribed = $record->getValue('addressBookSubscribedStatus') == Contact::STATUS_UNSUBSCRIBED;

        $permissions['subscribe']   = !$isSubscribed && !$wasUnsubscribed;
        $permissions['unsubscribe'] = $isSubscribed;

        return $permissions;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if ($datasource instanceof OrmDatasource) {
            $mlParameter = $datasource->getQueryBuilder()->getParameter('marketingListEntity');
            $marketingList = $mlParameter ? $mlParameter->getValue() : null;
            if ($marketingList instanceof MarketingList) {
                if ((bool)$this->registry->getManager()
                    ->getRepository('OroCRMDotmailerBundle:AddressBook')
                    ->findOneBy(['marketingList' => $marketingList])
                ) {
                    $config = $datagrid->getConfig();
                    $this->removeColumn($config, 'contactedTimes');

                    // join real subscriber status
                    $qb = $datasource->getQueryBuilder();
                    $rootAlias = $qb->getRootAliases()[0];
                    $rootEntity = $qb->getRootEntities()[0];
                    $qb->leftJoin(
                        'OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact',
                        'dm_ab_contact',
                        Join::WITH,
                        'dm_ab_contact.marketingListItemId = ' . $rootAlias . '.id AND ' .
                        'dm_ab_contact.marketingListItemClass = \'' . $rootEntity . '\''
                    )
                    ->leftJoin(
                        'dm_ab_contact.addressBook',
                        'dm_ab',
                        Join::WITH,
                        'IDENTITY(dm_ab_contact.addressBook) = dm_ab.id AND IDENTITY(dm_ab.marketingList) = ' .
                        $marketingList->getId()
                    )
                    ->addSelect('IDENTITY(dm_ab_contact.status) as addressBookSubscribedStatus');

                    $this->rewriteActionConfiguration($datagrid);
                }
            }
        }
    }

    /**
     * @param DatagridInterface $datagrid
     */
    protected function rewriteActionConfiguration(DatagridInterface $datagrid)
    {
        $config = $datagrid->getConfig();
        $actionConfiguration = [$this, 'getMarketingListItemPermissions'];
        $callable = function (ResultRecordInterface $record) use ($actionConfiguration, $config) {
            $result = call_user_func($actionConfiguration, $record, $config->offsetGetOr('actions', []));

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
     * @param DatagridConfiguration $config
     * @param string $fieldName
     */
    protected function removeColumn(DatagridConfiguration $config, $fieldName)
    {
        $config->offsetUnsetByPath(sprintf('[columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $fieldName));
    }
}
