<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

class AddressBookRepository extends EntityRepository
{
    /**
     * Get addressBook originIds which related with marketing lists
     *
     * @deprecated since 2.4. Please use getConnectedAddressBooks().
     *
     * @param Channel $channel
     *
     * @return array
     */
    public function getSyncedAddressBooksToSyncOriginIds(Channel $channel)
    {
        $entities = $this->getConnectedAddressBooks($channel, null, false);

        $addressBooks = [];
        foreach ($entities as $entity) {
            $addressBooks[] = [
                'originId' => $entity->getOriginId(),
            ];
        }

        return $addressBooks;
    }

    /**
     * Returns addressBook(s), connected with marketingList(s).
     *
     * @param Channel $channel
     * @param int|null $aBookId
     * @param bool $throwExceptionOnNotFound
     *
     * @return AddressBook[]
     *
     * @throws EntityNotFoundException
     */
    public function getConnectedAddressBooks(Channel $channel, $aBookId = null, $throwExceptionOnNotFound = true)
    {
        $qb = $this->createQueryBuilder('addressBook')
            ->where('addressBook.channel = :channel AND addressBook.marketingList IS NOT NULL')
            ->setParameter('channel', $channel);
        if ($aBookId) {
            $qb->andWhere('addressBook.id = :aBookId')
                ->setParameter('aBookId', $aBookId);
        }
        $result = $qb->getQuery()->execute();
        if ($aBookId && !$result && $throwExceptionOnNotFound) {
            throw new EntityNotFoundException(
                sprintf(
                    'Address book for ID %d, integration "%s" and existing marketing list was not found.',
                    $aBookId,
                    $channel->getName()
                )
            );
        }

        return $result;
    }

    /**
     * @deprecated since 2.4. Please use getConnectedAddressBooks().
     *
     * @param Channel $channel
     * @param int|null $addressBookId
     *
     * @return AddressBook[]
     *
     * @throws EntityNotFoundException
     */
    public function getAddressBooksToSync(Channel $channel, $addressBookId = null)
    {
        return $this->getConnectedAddressBooks($channel, $addressBookId);
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
            ->setParameter('channel', $channel)
            ->addOrderBy('addressBook.id');

        if (count($keepAddressBooks) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('addressBook.originId', $keepAddressBooks)
            );
        }

        return $qb;
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
                ->where($qb->expr()->in('addressBook.id', ':addressBookIds'))
                ->setParameter('addressBookIds', $addressBookIds)
                ->set('addressBook.lastImportedAt', ':lastImportedAt')
                ->setParameter('lastImportedAt', $importedAt)
                ->getQuery()
                ->execute();
        }
    }
}
