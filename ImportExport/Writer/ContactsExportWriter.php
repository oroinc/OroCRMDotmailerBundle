<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

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
    const BATCH_SIZE = 2000;

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
     * @param ManagerRegistry    $registry
     * @param DotmailerTransport $transport
     * @param ContextRegistry    $contextRegistry
     */
    public function __construct(
        ManagerRegistry $registry,
        DotmailerTransport $transport,
        ContextRegistry $contextRegistry
    ) {
        $this->registry = $registry;
        $this->transport = $transport;
        $this->contextRegistry = $contextRegistry;
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

                $addressBookItems[$addressBookOriginId][] = $item;
            }
            foreach ($addressBookItems as $addressBookOriginId => $items) {
                $this->updateAddressBookContacts($items, $manager, $addressBookOriginId);
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
        $exportEntity->setImportId($importStatus->id);

        $addressBook = $manager->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(['originId' => $addressBookOriginId, 'channel' => $this->getChannel()]);
        $exportEntity->setAddressBook($addressBook);

        $className = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $status = $manager->find($className, $importStatus->status);
        $exportEntity->setStatus($status);

        $manager->persist($exportEntity);
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
    }
}
