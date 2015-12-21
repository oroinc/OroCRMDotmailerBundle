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

    /**
     * @param \DateTime $importedAt
     * @param array $addressBookIds
     */
    public function bulkUpdateLastImportedAt(\DateTime $importedAt, array $addressBookIds)
    {
        if (count($addressBookIds)) {
            $qb = $this->createQueryBuilder('addressBook');
            $qb->update()
                ->where($qb->expr()->in('addressBook.id', $addressBookIds))
                ->set('addressBook.lastImportedAt', ':lastImportedAt')
                ->setParameter('lastImportedAt', $importedAt)
                ->getQuery()
                ->execute();
        }
    }
}
