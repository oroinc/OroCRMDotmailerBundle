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
        return $this->createQueryBuilder('addressBook')
            ->select('addressBook.originId')
            ->where('addressBook.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getScalarResult();
    }
}
