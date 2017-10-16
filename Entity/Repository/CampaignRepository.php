<?php

namespace Oro\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;

class CampaignRepository extends EntityRepository
{
    /**
     * @deprecated since 2.4. Please use getCampaignsStatistic().
     *
     * @param Channel $channel
     *
     * @return Campaign[]
     */
    public function getCampaignsToSyncStatistic(Channel $channel)
    {
        return $this->getCampaignsStatistic($channel);
    }

    /**
     * @param Channel $channel
     * @param int|null $aBookId
     *
     * @return Campaign[]
     */
    public function getCampaignsStatistic(Channel $channel, $aBookId = null)
    {
        $invalidCampaignStatuses = [
            Campaign::STATUS_SENDING,
            Campaign::STATUS_UNSENT
        ];
        $qb = $this->createQueryBuilder('campaign');
        $expression = $qb->expr();
        $qb->where('campaign.channel =:channel and campaign.deleted <> TRUE')
            ->leftJoin('campaign.status', 'campaignStatus')
            ->andWhere($expression->notIn('campaignStatus.id', $invalidCampaignStatuses))
            ->setParameter('channel', $channel);
        if ($aBookId) {
            $qb->innerJoin('campaign.addressBooks', 'addressBooks')
                ->andWhere('addressBooks.id = :aBookId')
                ->setParameter('aBookId', $aBookId);
        }
        $result = $qb->getQuery()->execute();

        return $result;
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
            ->setParameter('channel', $channel)
            ->andWhere('campaign.deleted <> TRUE')
            ->addOrderBy('campaign.id');

        if (count($keepCampaigns) > 0) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('campaign.originId', $keepCampaigns)
            );
        }

        return $qb;
    }

    /**
     *
     * @deprecated since 2.4. Please use getCampaigns().
     *
     * Get campaigns to collect activities statistics (clicks, opens)
     * They must have related email campaign
     *
     * @param Channel $channel
     * @return BufferedQueryResultIterator
     */
    public function getCampaignsToSynchronize(Channel $channel)
    {
        return $this->getCampaigns($channel, null);
    }

    /**
     * @param Channel $channel
     * @param int|null $aBookId
     *
     * @return BufferedQueryResultIterator
     */
    public function getCampaigns(Channel $channel, $aBookId = null)
    {
        $qb = $this->createQueryBuilder('campaign')
            ->innerJoin('campaign.addressBooks', 'addressBooks')
            ->innerJoin('campaign.emailCampaign', 'emailCampaign')
            ->innerJoin('emailCampaign.campaign', 'marketingCampaign')
            ->where('campaign.channel = :channel')
            ->andWhere('campaign.deleted = :deleted')
            ->addOrderBy('campaign.id')
            ->setParameter('channel', $channel)
            ->setParameter('deleted', false);
        if ($aBookId) {
            $qb->andWhere('addressBooks.id = :aBookId')
                ->setParameter('aBookId', $aBookId);
        }
        $query = $qb->getQuery();

        return new BufferedQueryResultIterator($query);
    }
}
