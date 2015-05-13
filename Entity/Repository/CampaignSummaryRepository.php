<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;

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
            ->join('cs.campaign', 'c')
            ->where('c.emailCampaign = :emailCampaign')
            ->setParameter('emailCampaign', $emailCampaign)
            ->getQuery()
            ->getSingleResult();
    }
}
