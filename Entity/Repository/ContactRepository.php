<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

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
        try {
            $result = $this->createQueryBuilder('contact')
                ->select('contact.createdAt')
                ->where('contact.channel = :channel')
                ->setParameter('channel', $channel)
                ->orderBy('contact.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
            $result = empty($result['createdAt']) ? null : $result['createdAt'];
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
    }
}
