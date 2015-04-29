<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ContactRepository extends EntityRepository
{
    /**
     * Get the date last contact was created on
     *
     * @param Channel $channel
     *
     * @return \DateTime|null
     */
    public function getLastCreatedAt(Channel $channel)
    {
        $result = $this->createQueryBuilder('contact')
            ->select('contact.createdAt')
            ->where('contact.channel = :channel')
            ->setParameter('channel', $channel)
            ->orderBy('contact.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $result = empty($result['createdAt']) ? null : $result['createdAt'];

        return $result;
    }
}
