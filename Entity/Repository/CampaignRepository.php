<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class CampaignRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     * @param array   $keepCampaigns
     *
     * @return QueryBuilder
     */
    public function getCampaignsForRemoveQB(Channel $channel, array $keepCampaigns)
    {
        $qb = $this->createQueryBuilder('campaign');
        $qb->select('campaign.id')
            ->where('campaign.channel =:channel');

        if (count($keepCampaigns) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('campaign.originId', $keepCampaigns)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }
}
