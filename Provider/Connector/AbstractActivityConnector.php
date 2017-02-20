<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;

abstract class AbstractActivityConnector extends AbstractDotmailerConnector
{
    /**
     * @return array
     */
    protected function getCampaignToSyncrhonize()
    {
        $campaigns = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Campaign')
            ->getCampaignsToSynchronize($this->getChannel());

        $campaignsToSynchronize = [];

        /** @var Campaign $campaign */
        foreach ($campaigns as $campaign) {
            $marketingCampaign = $campaign->getEmailCampaign()->getCampaign();
            $addressBooks = $campaign->getAddressBooks()->map(
                function (AddressBook $addressBook) {
                    return $addressBook->getId();
                }
            )->toArray();
            /**
             * full sync should be done for campaigns created after the last last sync
             */
            $isInit = $campaign->getCreatedAt() < $this->getLastSyncDate();
            $campaignsToSynchronize[] = [
                'originId'        => $campaign->getOriginId(),
                'emailCampaignId' => $campaign->getEmailCampaign()->getId(),
                'campaignId'      => $marketingCampaign->getId(),
                'addressBooks'    => $addressBooks,
                'isInit'          => $isInit
            ];
        }

        return $campaignsToSynchronize;
    }
}
