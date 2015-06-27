<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\Campaign;

class CampaignRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     *
     * @return Campaign[]
     */
    public function getCampaignsToSyncStatistic(Channel $channel)
    {
        $invalidCampaignStatuses = [
            Campaign::STATUS_SENDING,
            Campaign::STATUS_UNSENT
        ];
        $qb = $this->createQueryBuilder('campaign');
        $expression = $qb->expr();
        $qb->where('campaign.channel =:channel and campaign.deleted <> TRUE')
            ->leftJoin('campaign.status', 'campaignStatus')
            ->andWhere($expression->notIn('campaignStatus.id', $invalidCampaignStatuses));

        return $qb->getQuery()
            ->execute(['channel' => $channel]);
    }

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
            ->where('campaign.channel =:channel')
            ->andWhere('campaign.deleted <> TRUE');

        if (count($keepCampaigns) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('campaign.originId', $keepCampaigns)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }
}
