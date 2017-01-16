<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

abstract class AbstractActivityConnector extends AbstractDotmailerConnector
{
    /**
     * @return array
     */
    protected function getCampaignToSyncrhonize()
    {
        // Synchronize only campaign activities that are connected to marketing list.
        $campaigns = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Campaign')
            ->findBy(['channel' => $this->getChannel(), 'deleted' => false]);

        $activityRepository = $this->managerRegistry->getRepository('OroDotmailerBundle:Activity');
        $campaignsToSynchronize = [];

        foreach ($campaigns as $campaign) {
            $marketingCampaign = $campaign->getEmailCampaign()->getCampaign();
            //collect activities only if related marketing campaign exisits
            if ($marketingCampaign) {
                $campaignsToSynchronize[] = [
                    'originId'        => $campaign->getOriginId(),
                    'emailCampaignId' => $campaign->getEmailCampaign()->getId(),
                    'campaignId'      => $marketingCampaign->getId(),
                    'isInit'          => $activityRepository->isExistsActivityByCampaign($campaign),
                ];
            }
        }

        return $campaignsToSynchronize;
    }
}
