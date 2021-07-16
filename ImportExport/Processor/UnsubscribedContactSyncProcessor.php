<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\OutOfSyncMarketingListItemIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;

class UnsubscribedContactSyncProcessor implements ItemProcessorInterface, StepExecutionAwareInterface
{
    const CURRENT_BATCH_READ_ITEMS = 'currentBatchReadItems';

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    public function __construct(ManagerRegistry $registry, ContextRegistry $contextRegistry)
    {
        $this->registry = $registry;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        $items = $this->context->getValue(self::CURRENT_BATCH_READ_ITEMS) ?: [];
        $items[] = $item;
        $this->context->setValue(self::CURRENT_BATCH_READ_ITEMS, $items);

        $entityId = $item[MarketingListItemsQueryBuilderProvider::MARKETING_LIST_ITEM_ID];
        $marketingListUnsubscribedItem = new MarketingListUnsubscribedItem();
        $marketingListUnsubscribedItem->setEntityId($entityId);
        /** @var MarketingList $marketingList */
        $marketingList = $this->registry
            ->getRepository(MarketingList::class)
            ->find($item[OutOfSyncMarketingListItemIterator::MARKETING_LIST]);
        $marketingListUnsubscribedItem->setMarketingList($marketingList);

        return $marketingListUnsubscribedItem;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);
    }
}
