<?php

namespace OroCRM\Bundle\DotmailerBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaignStatistics;
use OroCRM\Bundle\CampaignBundle\Model\EmailCampaignStatisticsConnector;
use OroCRM\Bundle\DotmailerBundle\Entity\Activity;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Provider\MarketingListProvider;

class UpdateEmailCampaignStatistics extends AbstractMarketingListEntitiesAction
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
     * @param EmailCampaignStatisticsConnector $campaignStatisticsConnector
     */
    public function setCampaignStatisticsConnector($campaignStatisticsConnector)
    {
        $this->campaignStatisticsConnector = $campaignStatisticsConnector;
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
                    && $dmCampaign->hasAddressBooks()
                    && $dmCampaign->getAddressBooks()->first()
                    && $dmCampaign->getAddressBooks()->first()->getMarketingList();
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
        $marketingList = $dmCampaign->getAddressBooks()->first()->getMarketingList();
        $relatedEntities = $this->getMarketingListEntitiesByEmail($marketingList, $activity->getEmail());

        $em = $this->registry->getManager();
        foreach ($relatedEntities as $relatedEntity) {
            /** @var EmailCampaignStatistics $emailCampaignStatistics */
            $emailCampaignStatistics = $this->campaignStatisticsConnector->getStatisticsRecord(
                $emailCampaign,
                $relatedEntity
            );

            $marketingListItem = $emailCampaignStatistics->getMarketingListItem();
            $marketingListItem->setLastContactedAt($activity->getDateSent());
            $emailCampaignStatistics->setOpenCount($activity->getNumOpens());
            $emailCampaignStatistics->setClickCount($activity->getNumClicks());
            $emailCampaignStatistics->setBounceCount((int)$activity->isSoftBounced() + (int)$activity->isHardBounced());
            $emailCampaignStatistics->setUnsubscribeCount((int)$activity->isUnsubscribed());

            $em->persist($emailCampaignStatistics);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->campaignStatisticsConnector) {
            throw new \InvalidArgumentException('EmailCampaignStatisticsConnector is not provided');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitiesQueryBuilder(MarketingList $marketingList)
    {
        return $this->marketingListProvider
            ->getMarketingListEntitiesQueryBuilder($marketingList, MarketingListProvider::FULL_ENTITIES_MIXIN);
    }
}
