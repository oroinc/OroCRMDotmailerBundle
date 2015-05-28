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
        /**
         * Array of Address Books Ids was divided into parts because of
         * "IN" statement has Limit of size based on DB settings.
         * For mysql "IN" statement limited by "max_allowed_packet" setting.
         *
         * @link https://dev.mysql.com/doc/refman/5.0/en/comparison-operators.html#function_in
         */
        $chunks = array_chunk($keepCampaigns, 1000);

        $qb = $this->createQueryBuilder('campaign');
        $qb->select('campaign.id')
            ->where('campaign.channel =:channel');

        foreach ($chunks as $keepAddressBooks) {
            $qb->andWhere(
                $qb->expr()
                    ->notIn('campaign.originId', $keepAddressBooks)
            );
        }

        return $qb->setParameters(['channel' => $channel]);
    }
}
