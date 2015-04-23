<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class AddressBookRepository extends EntityRepository
{
    /**
     * Get addressBook Ids which related with marketing lists
     *
     * @param Channel $channel
     *
     * @return array
     */
    public function getAddressBooksToSyncOriginIds(Channel $channel)
    {
        $qb = $this->createQueryBuilder('a');

        $qb
            ->select('a.originId')
            ->where('a.channel = :channel AND a.marketingList IS NOT NULL')
            ->setParameter('channel', $channel);

        return $qb->getQuery()->getScalarResult();
    }
}
