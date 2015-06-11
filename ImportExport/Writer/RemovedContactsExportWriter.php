<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use DotMailer\Api\Exception;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Processor\RemovedExportProcessor;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;
use OroCRM\Bundle\DotmailerBundle\Model\ImportExportLogHelper;

class RemovedContactsExportWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DotmailerTransport
     */
    protected $transport;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ContextInterface
     */
    protected $context;

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
     * @param DotmailerTransport    $transport
     * @param ContextRegistry       $contextRegistry
     * @param LoggerInterface       $logger
     * @param ImportExportLogHelper $logHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        DotmailerTransport $transport,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger,
        ImportExportLogHelper $logHelper
    ) {
        $this->registry = $registry;
        $this->transport = $transport;
        $this->contextRegistry = $contextRegistry;
        $this->logger = $logger;
        $this->logHelper = $logHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /**
         * Clear already read items raw values
         */
        $this->context->setValue(RemovedExportProcessor::CURRENT_BATCH_READ_ITEMS, []);

        $repository = $this->registry->getRepository('OroCRMDotmailerBundle:AddressBookContact');

        $addressBookItems = [];
        foreach ($items as $item) {
            $addressBookItems[$item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY]][] = $item;
        }
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $em->beginTransaction();
        try {
            foreach ($addressBookItems as $addressBookOriginId => $items) {
                $this->removeAddressBookContacts($items, $repository, $addressBookOriginId);
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            if (!$em->isOpen()) {
                $this->registry->resetManager();
            }

            throw $e;
        }
    }


    /**
     * @param array            $items
     * @param EntityRepository $repository
     * @param int              $addressBookOriginId
     */
    protected function removeAddressBookContacts(array $items, EntityRepository $repository, $addressBookOriginId)
    {
        try {
            $removingItemsIds = [];
            $removingItemsOriginIds = [];

            /**
             * Remove Dotmailer Contacts from DB.
             * Smaller, than step batch used because of "IN" max length
             */
            foreach ($items as $item) {
                $removingItemsIds[] = $item['id'];
                $removingItemsOriginIds[] = $item['originId'];

                $this->context->incrementDeleteCount();
            }

            if (count($removingItemsIds) > 0) {
                $this->removeContacts($repository, $removingItemsIds);

                /**
                 * Remove Dotmailer Contacts from Dotmailer
                 * Operation is Async in Dotmailer side
                 */
                $this->transport->removeContactsFromAddressBook($removingItemsOriginIds, $addressBookOriginId);
            }

            $this->logBatchInfo($removingItemsOriginIds, $addressBookOriginId);
        } catch (Exception $e) {
            $this->logger
                ->warning(
                    "Remove contacts from Address Book '{$addressBookOriginId}' failed. Message: {$e->getMessage()}"
                );
        }
    }

    /**
     * @param array $items
     * @param int   $addressBookOriginId
     */
    protected function logBatchInfo(array $items, $addressBookOriginId)
    {
        $itemsCount = count($items);

        $memoryUsed = $this->logHelper->getMemoryConsumption();
        $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);

        $message = "$itemsCount Contacts removed from Dotmailer Address Book with Id: $addressBookOriginId.";
        $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

        $this->logger->info($message);
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);

        $this->transport->init($this->getChannel()->getTransport());
    }

    /**
     * @param EntityRepository $repository
     * @param array            $removingItemsIds
     */
    protected function removeContacts(EntityRepository $repository, array $removingItemsIds)
    {
        $qb = $repository->createQueryBuilder('contact');
        $qb->delete()
            ->where($qb->expr()
                ->in('contact.id', $removingItemsIds));
        $qb->getQuery()
            ->execute();
    }
}
