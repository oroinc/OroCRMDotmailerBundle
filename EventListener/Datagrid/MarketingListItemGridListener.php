<?php

namespace OroCRM\Bundle\DotmailerBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

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
                }
            }
        }

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
