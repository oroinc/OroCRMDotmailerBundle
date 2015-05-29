<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class AddressBookRepository extends EntityRepository
{
    /**
     * Get addressBook Ids which related with marketing lists
     *
     * @param Channel $channel
     *
     * @return array
     */
    public function getSyncedAddressBooksToSyncOriginIds(Channel $channel)
    {
        return $this->createQueryBuilder('addressBook')
            ->select('addressBook.originId')
            ->where('addressBook.channel = :channel and addressBook.marketingList IS NOT NULL')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Get addressBook Ids
     *
     * @param Channel $channel
     *
     * @return array
     */
    public function getAddressBooksToSyncOriginIds(Channel $channel)
    {
        return $this->createQueryBuilder('addressBook')
            ->select('addressBook.originId')
            ->where('addressBook.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBook[]
     */
    public function getAddressBooksToSync(Channel $channel)
    {
        return $this->createQueryBuilder('addressBook')
            ->where('addressBook.channel = :channel AND addressBook.marketingList IS NOT NULL')
            ->getQuery()
            ->execute(['channel' => $channel]);
    }

    /**
     * @param Channel $channel
     * @param array   $keepAddressBooks
     *
     * @return QueryBuilder
     */
    public function getAddressBooksForRemoveQB(Channel $channel, array $keepAddressBooks)
    {
        $qb = $this->createQueryBuilder('addressBook');
        $qb->select('addressBook.id')
            ->where('addressBook.channel =:channel');

        $qb->andWhere(
            $qb->expr()
                ->notIn('addressBook.originId', $keepAddressBooks)
        );

        return $qb->setParameters(['channel' => $channel]);
    }
}
