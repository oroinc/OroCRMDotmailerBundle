<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityNotFoundException;
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
     * @param int|null $addressBookId
     * @return AddressBook[]
     * @throws EntityNotFoundException
     */
    public function getAddressBooksToSync(Channel $channel, $addressBookId = null)
    {
        $queryBuilder = $this->createQueryBuilder('addressBook')
            ->where('addressBook.channel = :channel AND addressBook.marketingList IS NOT NULL');

        if ($addressBookId) {
            $queryBuilder->andWhere('addressBook = :addressBookId')
                ->setParameter('addressBookId', $addressBookId);
        }

        $result = $queryBuilder->getQuery()->execute(['channel' => $channel]);

        if ($addressBookId && !$result) {
            throw new EntityNotFoundException(
                sprintf(
                    'Address book for ID %d, integration "%s" and existing marketing list was not found.',
                    $addressBookId,
                    $channel->getName()
                )
            );
        }

        return $result;
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

        if (count($keepAddressBooks) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('addressBook.originId', $keepAddressBooks)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }
}
