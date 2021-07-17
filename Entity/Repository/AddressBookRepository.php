<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Doctrine repository for AddressBook entity.
 */
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
     * @param Channel $channel
     * @param int|null $addressBookId
     * @return AddressBook[]
     * @throws EntityNotFoundException
     */
    public function getAddressBooksToSync(Channel $channel, $addressBookId = null)
    {
        $queryBuilder = $this->createQueryBuilder('addressBook')
            ->where('addressBook.channel = :channel AND addressBook.marketingList IS NOT NULL')
            ->setParameter('channel', $channel);

        if ($addressBookId) {
            $queryBuilder->andWhere('addressBook = :addressBookId')
                ->setParameter('addressBookId', $addressBookId);
        }

        $result = $queryBuilder->getQuery()->execute();

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
     * @return AddressBook[]
     */
    public function getAddressBooksWithML()
    {
        $qb = $this->createQueryBuilder('addressBook')
            ->innerJoin('addressBook.marketingList', 'marketingList');

        $result = $qb->getQuery()->getResult();

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
            ->where('addressBook.channel =:channel')
            ->addOrderBy('addressBook.id');

        if (count($keepAddressBooks) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('addressBook.originId', $keepAddressBooks)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }

    public function bulkUpdateLastImportedAt(\DateTime $importedAt, array $addressBookIds)
    {
        if (count($addressBookIds)) {
            $qb = $this->createQueryBuilder('addressBook');
            $qb->update()
                ->where($qb->expr()->in('addressBook.id', ':addressBookIds'))
                ->setParameter('addressBookIds', $addressBookIds)
                ->set('addressBook.lastImportedAt', ':lastImportedAt')
                ->setParameter('lastImportedAt', $importedAt, Types::DATETIME_MUTABLE)
                ->getQuery()
                ->execute();
        }
    }
}
