<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * @Route("/dotmailer")
 */
class DotmailerController extends Controller
{
    const CHANNEL_SESSION_KEY = 'selected-integration-channel';

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
     *      class="OroMarketingListBundle:MarketingList",
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

    /**
     * @Route("/integration-connection/{id}",
     *      name="oro_dotmailer_integration_connection",
     *      requirements={"id"="\d+"},
     *      defaults={"id" = "0"}
     * )
     *
     * @Template("OroDotmailerBundle:Dotmailer:integrationConnection.html.twig")
     *
     * @param Request $request
     * @param Channel $channel
     * @return array
     */
    public function integrationConnectionAction(Request $request, Channel $channel = null)
    {
        if (!$channel) {
            $channel = $this->getCurrentChannel($request);
        }

        $form = $this->createForm('oro_dotmailer_integration_connection');
        $formData = $channel ? ['channel' => $channel] : [];
        $form->setData($formData);

        $loginUserUrl = false;
        $connectUrl = false;
        if ($channel) {
            $transport = $channel->getTransport();
            if ($transport->getClientId() && $transport->getClientKey()) {
                $oauthHelper = $this->get('oro_dotmailer.oauth_manager');
                $oauth = $this->getDoctrine()
                    ->getRepository('OroDotmailerBundle::OAuth')
                    ->findByChannelAndUser($channel, $this->getUser());
                if ($oauth) {
                    try {
                        $loginUserUrl = $oauthHelper->generateLoginUserUrl($transport, $oauth->getRefreshToken());
                        $this->get('session')->set(self::CHANNEL_SESSION_KEY, $channel->getId());
                    } catch (RuntimeException $e) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $e->getMessage()
                        );
                    } catch (\Exception $e) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('oro.dotmailer.integration.messsage.unable_to_connect')
                        );
                    }
                }
                $connectUrl = $oauthHelper->generateAuthorizeUrl($transport, $channel->getId());
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans(
                        'oro.dotmailer.integration.messsage.enter_client_id_client_key',
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

    /**
     * @param Request $request
     * @return Channel|null
     */
    protected function getCurrentChannel(Request $request)
    {
        $session = $this->get('session');
        $data = $request->get('oro_dotmailer_integration_connection');
        $channelId = isset($data['channel']) ? $data['channel'] : $session->get(self::CHANNEL_SESSION_KEY);
        $channel = null;
        if ($channelId) {
            $channel = $this->getDoctrine()
                ->getRepository(Channel::class)
                ->getOrLoadById($channelId);
        }

        return $channel;
    }
}
