<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

abstract class AbstractExportReader extends AbstractReader
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;


    /**
     * @param ManagerRegistry $registry
     *
     * @return RemovedContactsExportReader
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     *
     * @return RemovedContactsExportReader
     */
    public function setMarketingListItemsQueryBuilderProvider(
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
    ) {
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;

        return $this;
    }
}
