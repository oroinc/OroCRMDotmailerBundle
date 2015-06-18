<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;

class CampaignStatisticCachingProvider
{
    /**
     * @var EmailCampaignStatisticsConnector
     */
    protected $campaignStatisticsConnector;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $campaignStatistics = [];

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
        $record = $this->getCampaignStatisticRecord($emailCampaign, $relatedEntity);

        return $record;
    }

    public function clearCache()
    {
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
