<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;

class CampaignStatisticCachingProvider
{
    const BATCH_SIZE = 1000;

    /**
     * @var EmailCampaignStatisticsConnector
     */
    protected $campaignStatisticsConnector;

    /**
     * @var array
     */
    protected $campaignStatistics = [];

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param EmailCampaignStatisticsConnector $connector
     * @param ManagerRegistry                  $registry
     * @param DoctrineHelper                   $doctrineHelper
     */
    public function __construct(
        EmailCampaignStatisticsConnector $connector,
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper
    ) {
        $this->campaignStatisticsConnector = $connector;
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param EmailCampaign $emailCampaign
     * @param object        $relatedEntity
     *
     * @return null|EmailCampaignStatistics
     */
    public function getCampaignStatistic(EmailCampaign $emailCampaign, $relatedEntity)
    {
        $this->clearCacheIfOverflow();
        $record = $this->getCampaignStatisticRecord($emailCampaign, $relatedEntity);

        return $record;
    }

    protected function clearCacheIfOverflow()
    {
        if (count($this->campaignStatistics) < self::BATCH_SIZE) {
            return;
        }

        $this->registry
            ->getManager()
            ->flush(array_values($this->campaignStatistics));

        $this->campaignStatistics = [];
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
        if (!isset($this->campaignStatistics[$cacheKey])) {
            $record = $this->campaignStatisticsConnector
                ->getStatisticsRecord($emailCampaign, $relatedEntity);

            $this->registry
                ->getManager()
                ->persist($record);
            $this->campaignStatistics[$cacheKey] = $record;
        }

        return $this->campaignStatistics[$cacheKey];
    }
}
