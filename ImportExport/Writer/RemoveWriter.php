<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;
use Psr\Log\LoggerInterface;

/**
 * Batch job writer that is capable of removing items.
 */
class RemoveWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var ImportExportLogHelper
     */
    protected $logHelper;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger, ImportExportLogHelper $logHelper)
    {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->logHelper = $logHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $itemsCount = count($items);

        try {
            $em = $this->registry->getManager();
            foreach ($items as $item) {
                $em->remove($item);
            }

            $em->flush();
            $em->clear();

            $memoryUsed = $this->logHelper->getMemoryConsumption();
            $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);

            $message = "$itemsCount items removed";
            $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

            $this->logger->info($message);
        } catch (\Exception $e) {
            $this->logger->error("Removing $itemsCount items failed");
        }
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
