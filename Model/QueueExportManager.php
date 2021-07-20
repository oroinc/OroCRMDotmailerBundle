<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use DotMailer\Api\DataTypes\NullDataType;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Manage sync export statuses
 */
class QueueExportManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var DotmailerTransport
     */
    protected $dotmailerTransport;

    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $addressBookContactClassName;

    /**
     * @var int
     */
    private $totalErroneousAttempts = 10;

    /**
     * @var int
     */
    private $totalNotFinishedAttempts = 30;

    /**
     * @param ManagerRegistry    $managerRegistry
     * @param DotmailerTransport $dotmailerTransport
     * @param Executor           $executor
     * @param string             $addressBookContactClassName
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        DotmailerTransport $dotmailerTransport,
        Executor $executor,
        $addressBookContactClassName
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dotmailerTransport = $dotmailerTransport;
        $this->executor = $executor;
        $this->addressBookContactClassName = $addressBookContactClassName;
    }

    public function setTotalErroneousAttempts(int $attempts)
    {
        $this->totalErroneousAttempts = $attempts;
    }

    public function setTotalNotFinishedAttempts(int $attempts)
    {
        $this->totalNotFinishedAttempts = $attempts;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function updateExportResults(Channel $channel)
    {
        $exportRepository = $this->getAddressBookContactsExportRepository();

        try {
            $this->dotmailerTransport->init($channel->getTransport());
        } catch (RestClientException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return false;
        }

        $isExportFinished = true;
        foreach ($exportRepository->getNotFinishedExports($channel) as $export) {
            try {
                $dotmailerStatus = $this->dotmailerTransport->getImportStatus($export->getImportId());
            } catch (RestClientException $e) {
                $isExportFinished = false;

                $this->logger->error(
                    \sprintf(
                        '[EXPORT] Address book "%s" export report "%s" status failed: %s',
                        $export->getAddressBook()->getName(),
                        $export->getImportId(),
                        $e->getMessage()
                    ),
                    ['exception' => $e]
                );

                $this->processAttempts($export);

                continue;
            }

            if (!$dotmailerStatus->status || $dotmailerStatus->status instanceof NullDataType) {
                $isExportFinished = false;

                $this->logger->error(
                    \sprintf(
                        '[EXPORT] Address book "%s" export report "%s" status is empty',
                        $export->getAddressBook()->getName(),
                        $export->getImportId()
                    )
                );

                $this->processAttempts($export);

                continue;
            }

            $this->logger->info(
                \sprintf(
                    '[EXPORT] Address book "%s" export report "%s" status is "%s"',
                    $export->getAddressBook()->getName(),
                    $export->getImportId(),
                    $dotmailerStatus->status
                )
            );

            $exportStatus = $exportRepository->getStatus($dotmailerStatus->status);

            $export->setStatus($exportStatus);
            if (!$exportRepository->isFinishedStatus($exportStatus)) {
                $isExportFinished = false;

                $this->processAttempts($export, $this->totalNotFinishedAttempts);
            }
        }

        return  $this->processExportFaults($channel) && $isExportFinished;
    }

    public function processExportFaults(Channel $channel)
    {
        $jobResult = $this->startUpdateSkippedContactsStatusJob($channel);
        if (!$jobResult) {
            throw new RuntimeException('Update skipped contacts failed.');
        }
        if (!$jobResult->isSuccessful()) {
            return false;
        }

        $this->updateAddressBooksSyncStatus($channel);

        $this->managerRegistry->getManager()->flush();

        return true;
    }

    public function updateAddressBooksSyncStatus(Channel $channel)
    {
        $exportRepository = $this->getAddressBookContactsExportRepository();

        $addressBooks = $this->managerRegistry
            ->getRepository(AddressBook::class)
            ->findBy(['channel' => $channel]);

        foreach ($addressBooks as $addressBook) {
            $addressBookExports = $exportRepository->getExportsByAddressBook($addressBook);

            $isExportFinished = true;
            $lastErrorStatus = null;
            foreach ($addressBookExports as $addressBookExport) {
                $status = $addressBookExport->getStatus();

                if ($exportRepository->isErrorStatus($status)) {
                    $lastErrorStatus = $status;
                    $isExportFinished = false;
                    break;
                }

                if (!$exportRepository->isFinishedStatus($status)) {
                    $isExportFinished = false;
                }
            }

            if ($isExportFinished) {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getFinishedStatus(), true);
            } elseif ($lastErrorStatus) {
                $this->updateAddressBookSyncStatus($addressBook, $lastErrorStatus, true);
            } else {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getNotFinishedStatus(), false);
            }
        }
    }

    /**
     * @param AddressBook       $addressBook
     * @param AbstractEnumValue $status
     * @param bool              $updateLastExportedAt
     */
    protected function updateAddressBookSyncStatus(
        AddressBook $addressBook,
        AbstractEnumValue $status,
        $updateLastExportedAt
    ) {
        $addressBook->setSyncStatus($status);
        if ($updateLastExportedAt) {
            $addressBook->setLastExportedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        $this->logger->info(
            \sprintf(
                '[EXPORT] Address book "%s" export status changed to %s',
                $addressBook->getName(),
                $status->getId()
            )
        );
    }

    /**
     * @param Channel $channel
     *
     * @return JobResult
     */
    protected function startUpdateSkippedContactsStatusJob(Channel $channel)
    {
        $configuration = [
            ProcessorRegistry::TYPE_IMPORT => [
                'entityName'     => $this->addressBookContactClassName,
                'channel'        => $channel->getId(),
                'channelType'    => $channel->getType(),
            ],
        ];

        return $this->executor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            'dotmailer_import_not_exported_contact',
            $configuration
        );
    }

    /**
     * @return AddressBookContactsExportRepository
     */
    private function getAddressBookContactsExportRepository()
    {
        return $this->managerRegistry->getRepository(AddressBookContactsExport::class);
    }

    /**
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    private function processAttempts(
        AddressBookContactsExport $export,
        int $numberOfAttempts = null
    ) {
        if (null === $numberOfAttempts) {
            $numberOfAttempts = $this->totalErroneousAttempts;
        }

        $exportRepository = $this->getAddressBookContactsExportRepository();
        $attempts = (int)$export->getSyncAttempts() + 1;
        $exportRepository->updateAddressBookContactsExportAttemptsCount($export, $attempts);

        if ($attempts >= $numberOfAttempts) {
            $status = $exportRepository->getStatus(AddressBookContactsExport::STATUS_UNKNOWN);
            $exportRepository->updateAddressBookContactsStatus($export, $status);
        }

        $this->managerRegistry->getManagerForClass(AddressBookContactsExport::class)->refresh($export);
    }
}
