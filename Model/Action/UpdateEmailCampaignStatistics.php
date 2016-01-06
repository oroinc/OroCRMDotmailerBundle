<?php

namespace OroCRM\Bundle\DotmailerBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\DotmailerBundle\Provider\CampaignStatisticProvider;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

class UpdateEmailCampaignStatistics extends AbstractMarketingListEntitiesAction
{
    /**
     * @var CampaignStatisticProvider
     */
    protected $campaignStatisticProvider;

    /**
     * @param CampaignStatisticProvider $campaignStatisticProvider
     *
     * @return UpdateEmailCampaignStatistics
     */
    public function setCampaignStatisticProvider(
        CampaignStatisticProvider $campaignStatisticProvider
    ) {
        $this->campaignStatisticProvider = $campaignStatisticProvider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowed($context)
    {
        $isAllowed = false;
        if ($context instanceof EntityAwareInterface) {
            $entity = $context->getEntity();
            if ($entity instanceof Activity) {
                $dmCampaign = $entity->getCampaign();
                $isAllowed = $dmCampaign
                    && $dmCampaign->getEmailCampaign()
                    && $dmCampaign->getEmailCampaign()->getMarketingList();
            }
        }

        return $isAllowed && parent::isAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->updateStatistics($context->getEntity());
    }

    /**
     * @param Activity $activity
     */
    protected function updateStatistics(Activity $activity)
    {
        $dmCampaign = $activity->getCampaign();
        $emailCampaign = $dmCampaign->getEmailCampaign();
        $marketingList = $emailCampaign->getMarketingList();
        $relatedEntities = $this->getMarketingListEntitiesByEmail($marketingList, $activity->getEmail());

        foreach ($relatedEntities as $relatedEntity) {
            /** @var EmailCampaignStatistics $emailCampaignStatistics */
            $emailCampaignStatistics = $this->campaignStatisticProvider
                ->getCampaignStatistic(
                    $emailCampaign,
                    $relatedEntity
                );

            $marketingListItem = $emailCampaignStatistics->getMarketingListItem();
            $marketingListItem->setLastContactedAt($activity->getDateSent());
            $emailCampaignStatistics->setOpenCount($activity->getNumOpens());
            $emailCampaignStatistics->setClickCount($activity->getNumClicks());
            $emailCampaignStatistics->setBounceCount((int)$activity->isSoftBounced() + (int)$activity->isHardBounced());
            $emailCampaignStatistics->setUnsubscribeCount((int)$activity->isUnsubscribed());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitiesQueryBuilder(MarketingList $marketingList)
    {
        return $this->marketingListItemsQueryBuilderProvider
            ->getCachedMarketingListEntitiesQB($marketingList);
    }
}
