<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\OutOfSyncMarketingListItemIterator;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;

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

    /**
     * @param ManagerRegistry $registry
     * @param ContextRegistry $contextRegistry
     */
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
        $marketingList = $this->registry
            ->getRepository('OroCRMMarketingListBundle:MarketingList')
            ->find($item[OutOfSyncMarketingListItemIterator::MARKETING_LIST]->getId());
        $marketingListUnsubscribedItem->setMarketingList($marketingList);

        return $marketingListUnsubscribedItem;
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);
    }
}
