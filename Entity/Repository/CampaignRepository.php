<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
            ->andWhere('campaign.deleted <> TRUE')
            ->addOrderBy('campaign.id');

        if (count($keepCampaigns) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('campaign.originId', $keepCampaigns)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }

    /**
     * Get campaigns to collect activities statistics (clicks, opens)
     * They must have related email campaign
     *
     * @param Channel $channel
     * @return BufferedQueryResultIterator
     */
    public function getCampaignsToSynchronize(Channel $channel)
    {
        $qb = $this->createQueryBuilder('campaign');
        $qb->innerJoin('campaign.addressBooks', 'addressBooks')
            ->innerJoin('campaign.emailCampaign', 'emailCampaign')
            ->innerJoin('emailCampaign.campaign', 'marketingCampaign')
            ->where('campaign.channel = :channel')
            ->andWhere('campaign.deleted = :deleted')
            ->addOrderBy('campaign.id')
            ->setParameters(
                [
                    'channel' => $channel,
                    'deleted' => false
                ]
            );

        $query = $qb->getQuery();

        return new BufferedQueryResultIterator($query);
    }
}
