<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationConnectionType;
use Oro\Bundle\DotmailerBundle\Model\OAuthManager;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves dotmailer actions.
 *
 * @Route("/dotmailer")
 */
class DotmailerController extends AbstractController
{
    const CHANNEL_SESSION_KEY = 'selected-integration-channel';

    /**
     * @Route("/email-campaign-status/{entity}",
     *      name="oro_dotmailer_email_campaign_status",
     *      requirements={"entity"="\d+"})
     * @ParamConverter("emailCampaign",
     *      class="OroCampaignBundle:EmailCampaign",
     *      options={"id" = "entity"})
     * @AclAncestor("oro_email_campaign_view")
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
     * @AclAncestor("oro_marketing_list_view")
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
     * @param Request $request
     * @return JsonResponse
     */
    public function pingAction(Request $request)
    {
        if (!$this->isGranted('oro_integration_create') && !$this->isGranted('oro_integration_update')) {
            throw new AccessDeniedException();
        }

        $username = $request->get('username');
        $password = $request->get('password');

        $dotmailerResourceFactory = $this->get(DotmailerResourcesFactory::class);
        try {
            $dotmailerResourceFactory->createResources($username, $password);
            $result = [
                'msg' => $this->get(TranslatorInterface::class)
                    ->trans('oro.dotmailer.integration.connection_successful.label')
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
     * @Template("@OroDotmailer/Dotmailer/integrationConnection.html.twig")
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

        $form = $this->createForm(IntegrationConnectionType::class);
        $formData = $channel ? ['channel' => $channel] : [];
        $form->setData($formData);

        $loginUserUrl = false;
        $connectUrl = false;
        if ($channel) {
            $transport = $channel->getTransport();
            if ($transport->getClientId() && $transport->getClientKey()) {
                $oauthHelper = $this->get(OAuthManager::class);
                $oauth = $this->getDoctrine()
                    ->getRepository('OroDotmailerBundle:OAuth')
                    ->findByChannelAndUser($channel, $this->getUser());
                if ($oauth) {
                    try {
                        $loginUserUrl = $oauthHelper->generateLoginUserUrl($transport, $oauth->getRefreshToken());
                        $request->getSession()->set(self::CHANNEL_SESSION_KEY, $channel->getId());
                    } catch (RuntimeException $e) {
                        $request->getSession()->getFlashBag()->add(
                            'error',
                            $e->getMessage()
                        );
                    } catch (\Exception $e) {
                        $request->getSession()->getFlashBag()->add(
                            'error',
                            $this->get(TranslatorInterface::class)
                                ->trans('oro.dotmailer.integration.messsage.unable_to_connect')
                        );
                    }
                }
                $connectUrl = $oauthHelper->generateAuthorizeUrl($transport, $channel->getId());
            } else {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    $this->get(TranslatorInterface::class)->trans(
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
        $data = $request->get('oro_dotmailer_integration_connection');
        $channelId = $data['channel'] ?? $request->getSession()->get(self::CHANNEL_SESSION_KEY);
        $channel = null;
        if ($channelId) {
            $channel = $this->get(ManagerRegistry::class)
                ->getRepository(Channel::class)
                ->getOrLoadById($channelId);
        }

        return $channel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ManagerRegistry::class,
                DotmailerResourcesFactory::class,
                TranslatorInterface::class,
                OAuthManager::class
            ]
        );
    }
}
