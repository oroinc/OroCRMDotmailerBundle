<?php

namespace Oro\Bundle\DotmailerBundle\Step;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Batch job item step for rejected contacts.
 */
class ImportRejectedExportsStep extends ItemStep
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    #[\Override]
    public function doExecute(StepExecution $stepExecution)
    {
        parent::doExecute($stepExecution);

        $channel = $this->getChannel($stepExecution);
        if (!$channel) {
            throw new RuntimeException('Channel not found');
        }

        $this->registry
            ->getRepository(AddressBookContactsExport::class)
            ->setRejectedExportFaultsProcessed($channel);
    }

    /**
     * @param ManagerRegistry|null $registry
     *
     * @return ExportItemStep
     */
    public function setRegistry(?ManagerRegistry $registry = null)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param ContextRegistry|null $contextRegistry
     *
     * @return ExportItemStep
     */
    public function setContextRegistry(?ContextRegistry $contextRegistry = null)
    {
        $this->contextRegistry = $contextRegistry;

        return $this;
    }

    /**
     * @param StepExecution $stepExecution
     *
     * @return Channel
     */
    protected function getChannel(StepExecution $stepExecution)
    {
        $context = $this->contextRegistry->getByStepExecution($stepExecution);
        return $this->registry
            ->getRepository(Channel::class)
            ->getOrLoadById($context->getOption('channel'));
    }
}
