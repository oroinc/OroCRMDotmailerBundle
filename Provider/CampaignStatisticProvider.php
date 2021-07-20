<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use Oro\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CampaignStatisticProvider
{
    const CAMPAIGN_STATISTIC_CACHE = 'campaignStatisticCache';

    /**
     * @var CacheProvider
     */
    protected $cachingProvider;

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
