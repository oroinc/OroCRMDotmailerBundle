<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Reader;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\ImportExport\JobContextComposite;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Psr\Log\LoggerInterface;

/**
 * Base batch job reader.
 */
abstract class AbstractReader extends IteratorBasedReader
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ContextInterface
     */
    protected $jobContext;

    /**
     * @var ConnectorContextMediator
     */
    protected $contextMediator;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $contextMediator,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger
    ) {
        parent::__construct($contextRegistry);
        $this->contextMediator = $contextMediator;
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->jobContext = new JobContextComposite($stepExecution, $this->contextRegistry);
        parent::setStepExecution($stepExecution);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->context = $context;
        $this->initializeReader();
    }

    abstract protected function initializeReader();

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->context);
    }
}
