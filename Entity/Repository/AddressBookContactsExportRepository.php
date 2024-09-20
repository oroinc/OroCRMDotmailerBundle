<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * ORM repository for AddressBookContactsExport entity.
 */
class AddressBookContactsExportRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->select('addressBookContactExport.id')
            ->innerJoin(
                EnumOption::class,
                'status',
                Join::WITH,
                "JSON_EXTRACT(addressBookContactExport.serialized_data, 'status') = status.id"
            )
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id = :status')
            ->setMaxResults(1)
            ->setParameters(
                [
                    'channel' => $channel,
                    'status' => ExtendHelper::buildEnumOptionId(
                        AddressBookContactsExport::STATUS_ENUM_CODE,
                        AddressBookContactsExport::STATUS_NOT_FINISHED
                    )
                ]
            );

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result === null;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFaultsProcessed(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $notProcessedExportFaultsCount = $qb->select('COUNT(addressBookContactExport.id)')
            ->where('addressBookContactExport.faultsProcessed = :faultsProcessed')
            ->andWhere('addressBookContactExport.channel = :channel')
            ->setParameters(['faultsProcessed' => false, 'channel' => $channel])
            ->getQuery()
            ->getSingleScalarResult();

        return $notProcessedExportFaultsCount == 0;
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBookContactsExport[]
     */
    public function getNotFinishedExports(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin(
            EnumOption::class,
            'status',
            Join::WITH,
            "JSON_EXTRACT(addressBookContactExport.serialized_data, 'status') = status.id"
        )
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id =:status');

        return $qb->getQuery()
            ->execute(
                [
                    'channel' => $channel,
                    'status' => ExtendHelper::buildEnumOptionId(
                        AddressBookContactsExport::STATUS_ENUM_CODE,
                        AddressBookContactsExport::STATUS_NOT_FINISHED
                    )
                ]
            );
    }

    /**
     * Get list of exports of address book sorted by date in descending order.
     *
     * @param AddressBook $addressBook
     *
     * @return AddressBookContactsExport[]
     */
    public function getExportsByAddressBook(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->where('addressBookContactExport.addressBook =:addressBook')
            ->orderBy('addressBookContactExport.updatedAt', 'desc');

        return $qb->getQuery()->execute(['addressBook' => $addressBook]);
    }

    /**
     * Get Dotmailer status object (enum "dm_import_status").
     *
     * @param string $statusCode
     * @return EnumOptionInterface
     * @throws EntityNotFoundException
     */
    public function getStatus($statusCode)
    {
        $statusRepository = $this->getEntityManager()->getRepository(EnumOption::class);

        /** @var EnumOptionInterface|null $result */
        $result = $statusRepository->find(
            ExtendHelper::buildEnumOptionId(
                AddressBookContactsExport::STATUS_ENUM_CODE,
                $statusCode
            )
        );

        if (!$result) {
            throw new EntityNotFoundException(
                sprintf(
                    'Dotmailer import status "%s" was not found.',
                    $statusCode
                )
            );
        }

        return $result;
    }

    public function getFinishedStatus()
    {
        return $this->getStatus(AddressBookContactsExport::STATUS_FINISH);
    }

    public function getNotFinishedStatus()
    {
        return $this->getStatus(AddressBookContactsExport::STATUS_NOT_FINISHED);
    }

    /**
     * @param EnumOptionInterface $status
     * @return bool
     */
    public function isFinishedStatus(EnumOptionInterface $status)
    {
        return $status->getInternalId() == AddressBookContactsExport::STATUS_FINISH;
    }

    /**
     * @param EnumOptionInterface $status
     * @return bool
     */
    public function isNotFinishedStatus(EnumOptionInterface $status)
    {
        return $status->getInternalId() == AddressBookContactsExport::STATUS_NOT_FINISHED;
    }

    /**
     * @param EnumOptionInterface $status
     * @return bool
     */
    public function isErrorStatus(EnumOptionInterface $status)
    {
        return $status->getInternalId() !== AddressBookContactsExport::STATUS_FINISH &&
            $status->getInternalId() !== AddressBookContactsExport::STATUS_NOT_FINISHED &&
            $status->getInternalId() !== AddressBookContactsExport::STATUS_UNKNOWN;
    }

    /**
     * @param Channel $channel
     *
     * @return string[]
     */
    public function getRejectedExportImportIds(Channel $channel)
    {
        return $this->getRejectedExportRestrictionQB($channel)
            ->select('addressBookContactExport.importId')
            ->getQuery()
            ->execute();
    }

    public function setRejectedExportFaultsProcessed(Channel $channel)
    {
        $qb = $this->getRejectedExportRestrictionQB($channel);
        $qb->update()
            ->set('addressBookContactExport.faultsProcessed', $qb->expr()->literal(true))
            ->getQuery()
            ->execute();
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBookContactsExport[]
     */
    public function getNotRejectedExports(Channel $channel)
    {
        return $this->getNotRejectedExportRestrictionsQB($channel)
            ->innerJoin('addressBookContactExport.addressBook', 'addressBook')
            ->addSelect('addressBook')
            ->getQuery()
            ->execute();
    }

    public function setNotRejectedExportFaultsProcessed(Channel $channel)
    {
        $qb = $this->getNotRejectedExportRestrictionsQB($channel);
        $qb->update()
            ->set('addressBookContactExport.faultsProcessed', $qb->expr()->literal(true))
            ->getQuery()
            ->execute();
    }

    public function updateAddressBookContactsExportAttemptsCount(AddressBookContactsExport $export, int $attempts)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->update();
        $qb->set('addressBookContactExport.syncAttempts', ':attempts');
        $qb->andWhere(
            'addressBookContactExport.id = :id'
        );

        $qb->setParameter('attempts', $attempts);
        $qb->setParameter('id', $export->getId());
        $query = $qb->getQuery();
        $query->execute();
    }

    public function updateAddressBookContactsStatus(
        AddressBookContactsExport $export,
        EnumOptionInterface $status
    ) {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->update();
        $qb->set(
            'addressBookContactExport.serialized_data',
            "JSONB_SET(addressBookContactExport.serialized_data, '{status}', :status)"
        );
        $qb->set('addressBookContactExport.faultsProcessed', ':faultsProcessed');
        $qb->andWhere('addressBookContactExport.id = :id');
        $qb->setParameter('status', $status->getId(), Types::JSON);
        $qb->setParameter('faultsProcessed', true);
        $qb->setParameter('id', $export->getId());
        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * @param Channel      $channel
     *
     * @return QueryBuilder
     */
    protected function getNotRejectedExportRestrictionsQB(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->where(
            $qb->expr()->notIn(
                "JSON_EXTRACT(addressBookContactExport.serialized_data, 'status')",
                ':rejectedExportStatuses'
            )
        )
            ->andWhere('addressBookContactExport.channel = :channel')
            ->setParameter('rejectedExportStatuses', $this->getRejectedStatuses())
            ->setParameter('channel', $channel);

        return $qb;
    }

    /**
     * @param Channel      $channel
     *
     * @return QueryBuilder
     */
    protected function getRejectedExportRestrictionQB(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->where(
            $qb->expr()->in(
                "JSON_EXTRACT(addressBookContactExport.serialized_data, 'status')",
                ':rejectedExportStatuses'
            )
        )
            ->setParameter('rejectedExportStatuses', $this->getRejectedStatuses())
            ->andWhere('addressBookContactExport.channel = :channel')
            ->setParameter('channel', $channel);

        return $qb;
    }

    private function getRejectedStatuses(): array
    {
        return [
            ExtendHelper::buildEnumOptionId(
                AddressBookContactsExport::STATUS_ENUM_CODE,
                AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG,
            )
        ];
    }
}
