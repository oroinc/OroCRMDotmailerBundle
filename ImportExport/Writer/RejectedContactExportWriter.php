<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use OroCRM\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

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

    /**
     * @param ManagerRegistry       $registry
     * @param ContextRegistry       $contextRegistry
     * @param LoggerInterface       $logger
     * @param ImportExportLogHelper $logHelper
     */
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
