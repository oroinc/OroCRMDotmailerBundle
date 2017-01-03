<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\DotmailerBundle\Exception\RestClientException;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
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

    /**
     * @Route("/ping", name="orocrm_dotmailer_ping")
     * @AclAncestor("orocrm_dotmailer")
     */
    public function pingAction()
    {
        $username = $this->getRequest()->get('username');
        $password = $this->getRequest()->get('password');

        $dotmailerResourceFactory = $this->get('orocrm_dotmailer.transport.resources_factory');
        try {
            $dotmailerResourceFactory->createResources($username, $password);
            $result = [
                'msg' => $this->get('translator')->trans('orocrm.dotmailer.integration.connection_successful.label')
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

    /**
     * @Route("/integration-connection/{id}",
     *      name="orocrm_dotmailer_integration_connection",
     *      requirements={"id"="\d+"},
     *      defaults={"id" = "0"}
     * )
     *
     * @Template("OroCRMDotmailerBundle:Dotmailer:integrationConnection.html.twig")
     *
     * @param Request $request
     * @param Channel $channel
     * @return array
     */
    public function integrationConnectionAction(Request $request, Channel $channel = null)
    {
        $data = $request->get('orocrm_dotmailer_integration_connection');
        if (isset($data['channel'])) {
            $channel = $this->getDoctrine()
                ->getRepository('OroIntegrationBundle:Channel')
                ->getOrLoadById($data['channel']);
        }

        $form = $this->createForm('orocrm_dotmailer_integration_connection');
        $formData = $channel ? ['channel' => $channel] : [];
        $form->setData($formData);

        $loginUserUrl = false;
        $connectUrl = false;
        if ($channel) {
            $transport = $channel->getTransport();
            if ($transport->getClientId() && $transport->getClientKey()) {
                $oauthHelper = $this->get('orocrm_dotmailer.oauth_manager');
                $oauth = $this->getDoctrine()
                    ->getRepository('OroCRMDotmailerBundle::OAuth')
                    ->findByChannelAndUser($channel, $this->getUser());
                if ($oauth) {
                    try {
                        $loginUserUrl = $oauthHelper->generateLoginUserUrl($transport, $oauth->getRefreshToken());
                    } catch (RuntimeException $e) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $e->getMessage()
                        );
                    } catch (\Exception $e) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('orocrm.dotmailer.integration.messsage.unable_to_connect')
                        );
                    }
                }
                $connectUrl = $oauthHelper->generateAuthorizeUrl($transport, $channel->getId());
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans(
                        'orocrm.dotmailer.integration.messsage.enter_client_id_client_key',
                        ['%update_url%' => $this->generateUrl('oro_integration_update', ['id' => $channel->getId()])]
                    )
                );
            }
        }

        return [
            'form'         => $form->createView(),
            'entity'       => $channel,
            'loginUserUrl' => $loginUserUrl,
            'connectUrl'   => $connectUrl,
        ];
    }
}
