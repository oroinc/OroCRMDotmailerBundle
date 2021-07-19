<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\DotmailerBundle\ImportExport\Processor\RemovedExportProcessor;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerInterface;

/**
 * Remove Contact from Address Book in Dotmailer, remove Address Book Contact internally
 */
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

        $repository = $this->registry->getRepository('OroDotmailerBundle:AddressBookContact');

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
        $removingItemsIds = [];
        $removingItemsOriginIds = [];

        foreach ($items as $item) {
            if (empty($item['id']) || empty($item['originId'])) {
                continue;
            }

            $removingItemsIds[] = $item['id'];
            $removingItemsOriginIds[] = $item['originId'];
            $this->context->incrementDeleteCount();
        }

        try {
            $removingItemsOriginIds = $this->prepareIds($removingItemsOriginIds);
            if ($removingItemsOriginIds) {
                $this->transport->removeContactsFromAddressBook($removingItemsOriginIds, $addressBookOriginId);
                $this->logBatchInfo($removingItemsOriginIds, $addressBookOriginId);
            }
        } catch (\Exception $e) {
            $this->logger
                ->warning(
                    "Remove contacts from Address Book '{$addressBookOriginId}' failed. Message: {$e->getMessage()}",
                    ['exception' => $e]
                );

            return;
        }

        $this->removeContacts($repository, $removingItemsIds);
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

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);

        $this->transport->init($this->getChannel()->getTransport());
    }

    protected function removeContacts(EntityRepository $repository, array $removingItemsIds)
    {
        $removingItemsIds = $this->prepareIds($removingItemsIds);
        if (!$removingItemsIds) {
            return;
        }

        $qb = $repository->createQueryBuilder('contact');
        $qb->delete()
            ->where($qb->expr()->in('contact.id', ':removingItemsIds'))
            ->setParameter('removingItemsIds', $removingItemsIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $ids
     * @return array
     */
    protected function prepareIds(array $ids)
    {
        $ids = array_filter($ids);
        if (!$ids) {
            return [];
        }

        sort($ids);

        return $ids;
    }
}
