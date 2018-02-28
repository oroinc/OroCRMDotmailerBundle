<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;

class CampaignSummaryRepository extends EntityRepository
{
    /**
     * Get Summary by email campaign
     *
     * @param EmailCampaign $emailCampaign
     *
     * @return array
     */
    public function getSummaryByEmailCampaign(EmailCampaign $emailCampaign)
    {
        return $this->createQueryBuilder('cs')
            ->select('cs')
            ->innerJoin('cs.campaign', 'c')
            ->where('c.emailCampaign = :emailCampaign')
            ->setParameter('emailCampaign', $emailCampaign)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
