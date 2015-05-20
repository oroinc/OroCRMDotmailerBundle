<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;

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
            ->innerJoin('addressBookContactExport.addressBook', 'addressBook')
            ->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBook.channel =:channel')
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
     * @param $channel
     *
     * @return AddressBookContactsExport[]
     */
    public function getNotFinishedImports($channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin('addressBookContactExport.addressBook', 'addressBook')
            ->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBook.channel =:channel')
            ->andWhere('status.id =:status');

        return $qb->getQuery()
            ->execute(
                [
                    'channel' => $channel,
                    'status' => AddressBookContactsExport::STATUS_NOT_FINISHED
                ]
            );
    }
}
