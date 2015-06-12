<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroCRM\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class RemoveAddressBookWriter implements ItemWriterInterface, StepExecutionAwareInterface
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

    /**
     * @param ManagerRegistry       $registry
     * @param LoggerInterface       $logger
     * @param ImportExportLogHelper $logHelper
     */
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

            $message = "$itemsCount Address Books removed";
            $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

            $this->logger->info($message);
        } catch (\Exception $e) {
            $this->logger->error("Removing $itemsCount Address Books failed");
        }
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
