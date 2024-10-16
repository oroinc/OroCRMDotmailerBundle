<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use DotMailer\Api\Exception;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\ImportExport\DataConverter\ContactDataConverter;
use Oro\Bundle\DotmailerBundle\Model\ImportExportLogHelper;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\RemovedContactsExportIterator;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\CsvEchoWriter;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerInterface;

/**
 * Batch job writer for contacts export.
 */
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
     * @var ImportExportLogHelper
     */
    protected $logHelper;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    public function __construct(
        ManagerRegistry $registry,
        DotmailerTransport $transport,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger,
        ImportExportLogHelper $logHelper
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->transport = $transport;
        $this->contextRegistry = $contextRegistry;
        $this->logger = $logger;
        $this->logHelper = $logHelper;
    }

    #[\Override]
    public function write(array $items)
    {
        /** @var EntityManager $manager */
        $manager = $this->registry->getManager();
        try {
            $manager->beginTransaction();
            $addressBookItems = [];
            $addressBookContactIds = [];

            foreach ($items as $item) {
                $addressBookOriginId = $item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY];
                if (!isset($addressBookItems[$addressBookOriginId])) {
                    $addressBookItems[$addressBookOriginId] = [];
                }
                $addressBookContactIds[$addressBookOriginId][] = $item[ContactDataConverter::ADDRESS_BOOK_CONTACT_ID];

                unset($item[ContactDataConverter::ADDRESS_BOOK_CONTACT_ID]);
                unset($item[RemovedContactsExportIterator::ADDRESS_BOOK_KEY]);

                $addressBookItems[$addressBookOriginId][] = $item;

                $this->context->incrementReplaceCount();
            }
            foreach ($addressBookItems as $addressBookOriginId => $items) {
                $this->updateAddressBookContacts(
                    $items,
                    $addressBookContactIds[$addressBookOriginId],
                    $addressBookOriginId
                );
            }

            $manager->flush();
            $manager->commit();
            $manager->clear();
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
     * @param array         $addressBookContactIds
     * @param int           $addressBookOriginId
     */
    protected function updateAddressBookContacts(array $items, array $addressBookContactIds, $addressBookOriginId)
    {
        $manager = $this->registry->getManager();

        ob_start();
        parent::write($items);
        $csv = ob_get_contents();
        ob_end_clean();

        /**
         * Reset CsfFileStreamWriter
         */
        $this->fileHandle = null;
        $this->header = null;
        try {
            $importStatus = $this->transport->exportAddressBookContacts($csv, $addressBookOriginId);
        } catch (Exception $e) {
            $this->logger
                ->warning(
                    "Export Contacts to Address Book {$addressBookOriginId} failed. Message: {$e->getMessage()}"
                );

            return;
        }

        $exportEntity = new AddressBookContactsExport();
        $importId = (string)$importStatus->id;
        $exportEntity->setImportId($importId);

        $channel = $this->getChannel();
        $addressBook = $manager->getRepository(AddressBook::class)
            ->findOneBy(['originId' => $addressBookOriginId, 'channel' => $channel]);
        $exportEntity->setAddressBook($addressBook);

        /** @var EnumOptionInterface $status */
        $importStatusId = (string)$importStatus->status;
        $status = $manager->find(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId('dm_import_status', $importStatusId)
        );
        $exportEntity->setStatus($status);
        $exportEntity->setChannel($channel);

        $manager->getRepository(AddressBookContact::class)
            ->bulkUpdateAddressBookContactsExportId($addressBookContactIds, $importId);

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

        $stepExecutionTime = $this->logHelper->getFormattedTimeOfStepExecution($this->stepExecution);
        $memoryUsed = $this->logHelper->getMemoryConsumption();

        $message = "$itemsCount Contacts exported to Dotmailer Address Book with Id: $addressBookOriginId.";
        $message .= " Elapsed Time: {$stepExecutionTime}. Memory used: $memoryUsed MB.";

        $this->logger->info($message);
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        $this->context = $this->contextRegistry->getByStepExecution($stepExecution);
        $this->transport->init($this->getChannel()->getTransport());

        $this->setImportExportContext($this->context);
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->registry->getRepository(Channel::class)
            ->getOrLoadById($this->context->getOption('channel'));
    }
}
