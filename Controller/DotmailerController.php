<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * @Route("/dotmailer")
 */
class DotmailerController extends Controller
{
    /**
     * @Route("/email-campaign-status/{entity}",
     *      name="oro_dotmailer_email_campaign_status",
     *      requirements={"entity"="\d+"})
     * @ParamConverter("emailCampaign",
     *      class="OroCampaignBundle:EmailCampaign",
     *      options={"id" = "entity"})
     * @AclAncestor("oro_dotmailer")
     *
     * @Template
     *
     * @param EmailCampaign $emailCampaign
     * @return array
     */
    public function emailCampaignStatsAction(EmailCampaign $emailCampaign)
    {
        $campaign = $this->getDoctrine()
            ->getRepository('OroDotmailerBundle:CampaignSummary')
            ->getSummaryByEmailCampaign($emailCampaign);
        return ['campaignStats' => $campaign];
    }

    /**
     * @Route("/sync-status/{marketingList}",
     *      name="oro_dotmailer_sync_status",
     *      requirements={"marketingList"="\d+"})
     * @ParamConverter("marketingList",
     *      class="OroCRMMarketingListBundle:MarketingList",
     *      options={"id" = "marketingList"})
     * @AclAncestor("oro_dotmailer")
     *
     * @Template
     *
     * @param MarketingList $marketingList
     * @return array
     */
    public function marketingListSyncStatusAction(MarketingList $marketingList)
    {
        $addressBook = $this->getDoctrine()->getRepository('OroDotmailerBundle:AddressBook')
            ->findOneBy(['marketingList' => $marketingList]);

        return ['address_book' => $addressBook];
    }

    /**
     * @Route("/ping", name="oro_dotmailer_ping")
     * @AclAncestor("oro_dotmailer")
     */
    public function pingAction()
    {
        $username = $this->getRequest()->get('username');
        $password = $this->getRequest()->get('password');

        $dotmailerResourceFactory = $this->get('oro_dotmailer.transport.resources_factory');
        try {
            $dotmailerResourceFactory->createResources($username, $password);
            $result = [
                'msg' => $this->get('translator')->trans('oro.dotmailer.integration.connection_successful.label')
            ];
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            if ($exception instanceof RestClientException &&
                $exception->getPrevious() &&
                $exception->getPrevious()->getMessage()
            ) {
                $message = $exception->getPrevious()->getMessage();
            }
            $result = [
                'error' => $message
            ];
        }

        return new JsonResponse($result);
    }
}
