<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Psr\Log\LoggerInterface;

/**
 * Batch job writer that removes rejected contacts.
 */
class RejectedContactExportWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ImportExportLogHelper
     */
    protected $logHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ManagerRegistry $registry,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger,
        ImportExportLogHelper $logHelper
    ) {
        $this->registry = $registry;
        $this->contextRegistry = $contextRegistry;
        $this->logger = $logger;
        $this->logHelper = $logHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $em->beginTransaction();
        try {
            foreach ($items as $item) {
                $em->remove($item);
            }
            $em->flush();
            $em->commit();
            $em->clear();
        } catch (\Exception $e) {
            $em->rollback();
            if (!$em->isOpen()) {
                $this->registry->resetManager();
            }

            throw $e;
        }
    }
}
