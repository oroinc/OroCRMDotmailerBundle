<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * @Route("/dotmailer")
 */
class DotmailerController extends Controller
{
    /**
     * @Route("/email-campaign-status/{entity}",
     *      name="orocrm_dotmailer_email_campaign_status",
     *      requirements={"entity"="\d+"})
     * @ParamConverter("emailCampaign",
     *      class="OroCRMCampaignBundle:EmailCampaign",
     *      options={"id" = "entity"})
     * @AclAncestor("orocrm_dotmailer")
     *
     * @Template
     *
     * @param EmailCampaign $emailCampaign
     * @return array
     */
    public function emailCampaignStatsAction(EmailCampaign $emailCampaign)
    {
        $campaign = $this->getDoctrine()
            ->getRepository('OroCRMDotmailerBundle:CampaignSummary')
            ->getSummaryByEmailCampaign($emailCampaign);
        return ['campaignStats' => $campaign];
    }

    /**
     * @Route("/sync-status/{marketingList}",
     *      name="orocrm_dotmailer_sync_status",
     *      requirements={"marketingList"="\d+"})
     * @ParamConverter("marketingList",
     *      class="OroCRMMarketingListBundle:MarketingList",
     *      options={"id" = "marketingList"})
     * @AclAncestor("orocrm_dotmailer")
     *
     * @Template
     *
     * @param MarketingList $marketingList
     * @return array
     */
    public function marketingListSyncStatusAction(MarketingList $marketingList)
    {
        $addressBook = $this->getDoctrine()->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(['marketingList' => $marketingList]);

        return ['address_book' => $addressBook];
    }
}
