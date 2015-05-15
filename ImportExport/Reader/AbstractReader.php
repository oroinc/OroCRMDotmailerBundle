<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

abstract class AbstractReader extends IteratorBasedReader
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ConnectorContextMediator
     */
    protected $contextMediator;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param ConnectorContextMediator $contextMediator
     * @param ManagerRegistry          $managerRegistry
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $contextMediator,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($contextRegistry);
        $this->contextMediator = $contextMediator;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->context = $context;
        $this->afterInitialize();
    }

    abstract protected function afterInitialize();

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->context);
    }
}
