<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;

class RejectedContactExportProcessor implements StepExecutionAwareProcessor
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ContextRegistry $contextRegistry
     * @param LoggerInterface $logger
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextRegistry $contextRegistry, LoggerInterface $logger, ManagerRegistry $registry)
    {
        $this->contextRegistry = $contextRegistry;
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if (!$item instanceof AddressBookContact) {
            throw new RuntimeException(
                sprintf(
                    '"%s" expected, "%s" given',
                    'OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact',
                    is_object($item) ? get_class($item) : gettype($item)
                )
            );
        }

        if (!$item->getExportOperationType()) {
            throw new RuntimeException(
                sprintf(
                    'Export type is empty for Address book contact %s.',
                    $item->getId()
                )
            );
        }

        $exportOperationType = $item->getExportOperationType()->getId();
        if ($exportOperationType == AddressBookContact::EXPORT_UPDATE_CONTACT) {
            return null;
        }

        $this->logger->warning(
            sprintf(
                'Contact "%s" was not exported to address book "%s".',
                $item->getContact()->getId(),
                $item->getAddressBook()->getId()
            )
        );

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($this->stepExecution);
    }
}
