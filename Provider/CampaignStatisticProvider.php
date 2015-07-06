<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;

class CampaignStatisticProvider
{
    const CAMPAIGN_STATISTIC_CACHE = 'campaignStatisticCache';

    /**
     * @var CacheProvider
     */
    protected $cachingProvider;

    /**
     * @param EmailCampaignStatisticsConnector $connector
     * @param ManagerRegistry                  $registry
     * @param DoctrineHelper                   $doctrineHelper
     * @param CacheProvider                    $cachingProvider
     */
    public function __construct(
        EmailCampaignStatisticsConnector $connector,
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        CacheProvider $cachingProvider
    ) {
        $this->campaignStatisticsConnector = $connector;
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
        $this->cachingProvider = $cachingProvider;
    }

    /**
     * @param EmailCampaign $emailCampaign
     * @param object        $relatedEntity
     *
     * @return null|EmailCampaignStatistics
     */
    public function getCampaignStatistic(EmailCampaign $emailCampaign, $relatedEntity)
    {
        $record = $this->getCampaignStatisticRecord($emailCampaign, $relatedEntity);

        return $record;
    }

    /**
     * @param EmailCampaign $emailCampaign
     * @param object        $relatedEntity
     *
     * @return EmailCampaignStatistics|null
     */
    protected function getCampaignStatisticRecord(EmailCampaign $emailCampaign, $relatedEntity)
    {
        $cacheKey = $emailCampaign->getId() . '__' . $this->doctrineHelper->getSingleEntityIdentifier($relatedEntity);
        $statistic = $this->cachingProvider->getCachedItem(self::CAMPAIGN_STATISTIC_CACHE, $cacheKey);
        if (!$statistic) {
            $statistic = $this->campaignStatisticsConnector
                ->getStatisticsRecord($emailCampaign, $relatedEntity);

            $this->registry
                ->getManager()
                ->persist($statistic);
            $this->cachingProvider->setCachedItem(self::CAMPAIGN_STATISTIC_CACHE, $cacheKey, $statistic);
        }

        return $statistic;
    }
}
