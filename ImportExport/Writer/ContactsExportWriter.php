<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\CsvEchoWriter;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;

class ContactsExportWriter extends CsvEchoWriter implements StepExecutionAwareInterface
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
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry    $registry
     * @param DotmailerTransport $transport
     * @param ContextRegistry    $contextRegistry
     * @param LoggerInterface    $logger
     */
    public function __construct(
        ManagerRegistry $registry,
        DotmailerTransport $transport,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->transport = $transport;
        $this->contextRegistry = $contextRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var EntityManager $manager */
        $manager = $this->registry->getManager();
        try {
            $manager->beginTransaction();
            $addressBookItems = [];
            foreach ($items as $item) {
                $addressBookOriginId = $item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY];
                if (!isset($addressBookItems[$addressBookOriginId])) {
                    $addressBookItems[$addressBookOriginId] = [];
                }
                unset($item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY]);
                $addressBookItems[$addressBookOriginId][] = $item;

                $this->context->incrementReplaceCount();
            }
            foreach ($addressBookItems as $addressBookOriginId => $items) {
                $this->updateAddressBookContacts($items, $manager, $addressBookOriginId);
            }

            $manager->flush();
            $manager->commit();
            $manager->clear();
            $this->logger->info('Batch finished');
        } catch (\Exception $exception) {
            $manager->rollback();
            if (!$manager->isOpen()) {
                $this->registry->resetManager();
            }

            throw $exception;
        }
    }

    /**
     * @param array         $items
     * @param EntityManager $manager
     * @param int           $addressBookOriginId
     */
    protected function updateAddressBookContacts(array $items, EntityManager $manager, $addressBookOriginId)
    {
        ob_start();
        parent::write($items);
        $csv = ob_get_contents();
        ob_end_clean();

        /**
         * Reset CsfFileStreamWriter
         */
        $this->fileHandle = null;
        $this->header = null;

        $importStatus = $this->transport->exportAddressBookContacts($csv, $addressBookOriginId);

        $exportEntity = new AddressBookContactsExport();
        $importId = (string)$importStatus->id;
        $exportEntity->setImportId($importId);

        $channel = $this->getChannel();
        $addressBook = $manager->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(['originId' => $addressBookOriginId, 'channel' => $channel]);
        $exportEntity->setAddressBook($addressBook);

        $className = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $status = (string)$importStatus->status;
        $status = $manager->find($className, $status);
        $exportEntity->setStatus($status);
        $exportEntity->setChannel($channel);

        $manager->persist($exportEntity);

        $this->logBatchInfo($items, $addressBookOriginId);
    }


    /**
     * @param array $items
     * @param int   $addressBookOriginId
     */
    protected function logBatchInfo(array $items, $addressBookOriginId)
    {
        $itemsCount = count($items);
        $now = microtime(true);
        $previousBatchFinishTime = $this->context->getValue('recordingTime');

        $message = "$itemsCount Contacts exported to Dotmailer Address Book with Id: $addressBookOriginId.";
        if ($previousBatchFinishTime) {
            $spent = $now - $previousBatchFinishTime;
            $message .= "Time spent: $spent seconds.";
        }
        $memoryUsed = memory_get_usage(true);
        $memoryUsed = $memoryUsed / 1048576;
        $message .= "Memory used $memoryUsed MB .";

        $this->logger->info($message);

        $this->context->setValue('recordingTime', $now);
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
        $this->setImportExportContext($this->context);
        $this->transport->init($this->getChannel()->getTransport());
    }
}
