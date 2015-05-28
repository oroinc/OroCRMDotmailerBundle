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
        /**
         * Array of Address Books Ids was divided into parts because of
         * "IN" statement has Limit of size based on DB settings.
         * For mysql "IN" statement limited by "max_allowed_packet" setting.
         * @link https://dev.mysql.com/doc/refman/5.0/en/comparison-operators.html#function_in
         */
        $chunks = array_chunk($keepAddressBooks, 1000);

        $qb = $this->createQueryBuilder('addressBook');
        $qb->select('addressBook.id')
            ->where('addressBook.channel =:channel');

        foreach ($chunks as $keepAddressBooks) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('addressBook.originId', $keepAddressBooks)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }
}
