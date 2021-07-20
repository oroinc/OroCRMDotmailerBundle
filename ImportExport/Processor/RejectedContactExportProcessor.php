<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;
use Psr\Log\LoggerInterface;

/**
 * Dotmailer Export processor for rejected contacts.
 */
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
                    AddressBookContact::class,
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
