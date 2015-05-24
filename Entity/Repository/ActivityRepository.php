<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;

class ActivityRepository extends EntityRepository
{
    /**
     * Exists activity by campaign
     *
     * @param Campaign $campaign
     *
     * @return boolean
     */
    public function isExistsActivityByCampaign(Campaign $campaign)
    {
        $result = $this->createQueryBuilder('a')
            ->select('a')
            ->andWhere('a.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result != null ? : false;
    }
}
