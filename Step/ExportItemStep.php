<?php

namespace OroCRM\Bundle\DotmailerBundle\Step;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ExportItemStep extends ItemStep
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
        $channel = $this->getChannel($stepExecution);

        /**
         * Clear old Address Book Export records
         */
        $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->createQueryBuilder('abContactsExport')
            ->delete()
            ->where('abContactsExport.channel =:channel')
            ->getQuery()
            ->execute(['channel' => $channel]);

        parent::doExecute($stepExecution);

        /**
         * @var EntityRepository $addressBookContactRepository
         */
        $addressBookContactRepository = $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact');

        $addressBookContactRepository->createQueryBuilder('addressBookContact')
            ->update()
            ->where('addressBookContact.channel =:channel')
            ->set('addressBookContact.scheduledForExport', ':scheduledForExport')
            ->getQuery()
            ->execute(['channel' => $channel, 'scheduledForExport' => false]);
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
