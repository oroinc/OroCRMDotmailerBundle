<?php

namespace OroCRM\Bundle\DotmailerBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ImportExportErrorReportsStep extends ItemStep
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        parent::doExecute($stepExecution);

        $channel = $this->getChannel($stepExecution);
        $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->setNotRejectedExportFaultsProcessed($channel);
    }

    /**
     * @param ManagerRegistry $registry
     *
     * @return ExportItemStep
     */
    public function setRegistry(ManagerRegistry $registry = null)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param ContextRegistry $contextRegistry
     *
     * @return ExportItemStep
     */
    public function setContextRegistry(ContextRegistry $contextRegistry = null)
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
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($context->getOption('channel'));
    }
}
