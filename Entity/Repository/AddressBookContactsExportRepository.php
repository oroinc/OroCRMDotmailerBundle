<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;

class AddressBookContactsExportRepository extends EntityRepository
{
    protected $rejectedExportStatuses = [
        AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG,
        AddressBookContactsExport::STATUS_NOT_AVAILABLE_IN_THIS_VERSION,
        AddressBookContactsExport::STATUS_EXCEEDS_ALLOWED_CONTACT_LIMIT,
    ];

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->select('addressBookContactExport.id')
            ->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id =:status')
            ->setMaxResults(1)
            ->setParameters(
                [
                    'channel' => $channel,
                    'status' => AddressBookContactsExport::STATUS_NOT_FINISHED
                ]
            );

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result === null ? true : false;
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
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id =:status');

        return $qb->getQuery()
            ->execute(
                [
                    'channel' => $channel,
                    'status' => AddressBookContactsExport::STATUS_NOT_FINISHED
                ]
            );
    }

    /**
     * @param AddressBook $addressBook
     *
     * @return AddressBookContactsExport[]
     */
    public function getExportResults(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.addressBook =:addressBook')
            ->orderBy('addressBookContactExport.updatedAt', 'desc');

        return $qb->getQuery()->execute(['addressBook' => $addressBook]);
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

    /**
     * @param Channel $channel
     */
    public function setRejectedExportFaultsProcessed(Channel $channel)
    {
        $this->getRejectedExportRestrictionQB($channel)
            ->update()
            ->set('addressBookContactExport.faultsProcessed', true)
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

    /**
     * @param Channel $channel
     */
    public function setNotRejectedExportFaultsProcessed(Channel $channel)
    {
        $this->getNotRejectedExportRestrictionsQB($channel)
            ->update()
            ->set('addressBookContactExport.faultsProcessed', true)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Channel      $channel
     *
     * @return QueryBuilder
     */
    protected function getNotRejectedExportRestrictionsQB(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where($qb->expr()->notIn('addressBookContactExport.status', $this->rejectedExportStatuses))
            ->andWhere('addressBookContactExport.channel = :channel')
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
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where($qb->expr()->in('addressBookContactExport.status', $this->rejectedExportStatuses))
            ->andWhere('addressBookContactExport.channel = :channel')
            ->setParameter('channel', $channel);

        return $qb;
    }
}
