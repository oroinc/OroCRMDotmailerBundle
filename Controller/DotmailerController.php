<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\CampaignSummary;
use Oro\Bundle\DotmailerBundle\Entity\OAuth;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationConnectionType;
use Oro\Bundle\DotmailerBundle\Model\OAuthManager;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves dotmailer actions.
 */
#[Route(path: '/dotmailer')]
class DotmailerController extends AbstractController
{
    public const CHANNEL_SESSION_KEY = 'selected-integration-channel';

    /**
     *
     * @param EmailCampaign $emailCampaign
     * @return array
     */
    #[Route(
        path: '/email-campaign-status/{entity}',
        name: 'oro_dotmailer_email_campaign_status',
        requirements: ['entity' => '\d+']
    )]
    #[Template('@OroDotmailer/Dotmailer/emailCampaignStats.html.twig')]
    #[AclAncestor('oro_email_campaign_view')]
    public function emailCampaignStatsAction(
        #[MapEntity(id: 'entity')]
        EmailCampaign $emailCampaign
    ) {
        $campaign = $this->container->get('doctrine')
            ->getRepository(CampaignSummary::class)
            ->getSummaryByEmailCampaign($emailCampaign);
        return ['campaignStats' => $campaign];
    }

    /**
     *
     * @param MarketingList $marketingList
     * @return array
     */
    #[Route(
        path: '/sync-status/{marketingList}',
        name: 'oro_dotmailer_sync_status',
        requirements: ['marketingList' => '\d+']
    )]
    #[Template('@OroDotmailer/Dotmailer/marketingListSyncStatus.html.twig')]
    #[AclAncestor('oro_marketing_list_view')]
    public function marketingListSyncStatusAction(
        #[MapEntity(id: 'marketingList')]
        MarketingList $marketingList
    ) {
        $addressBook = $this->container->get('doctrine')->getRepository(AddressBook::class)
            ->findOneBy(['marketingList' => $marketingList]);

        return ['address_book' => $addressBook];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/ping', name: 'oro_dotmailer_ping')]
    #[CsrfProtection]
    public function pingAction(Request $request)
    {
        if (!$this->isGranted('oro_integration_create') && !$this->isGranted('oro_integration_update')) {
            throw new AccessDeniedException();
        }

        $username = $request->get('username');
        $password = $request->get('password');

        $dotmailerResourceFactory = $this->container->get(DotmailerResourcesFactory::class);
        try {
            $dotmailerResourceFactory->createResources($username, $password);
            $result = [
                'msg' => $this->container->get(TranslatorInterface::class)
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
     *
     * @param Request $request
     * @param Channel|null $channel
     * @return array
     */
    #[Route(
        path: '/integration-connection/{id}',
        name: 'oro_dotmailer_integration_connection',
        requirements: ['id' => '\d+'],
        defaults: ['id' => 0]
    )]
    #[Template('@OroDotmailer/Dotmailer/integrationConnection.html.twig')]
    public function integrationConnectionAction(Request $request, ?Channel $channel = null)
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
                $oauthHelper = $this->container->get(OAuthManager::class);
                $oauth = $this->container->get('doctrine')
                    ->getRepository(OAuth::class)
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
                            $this->container->get(TranslatorInterface::class)
                                ->trans('oro.dotmailer.integration.messsage.unable_to_connect')
                        );
                    }
                }
                $connectUrl = $oauthHelper->generateAuthorizeUrl($transport, $channel->getId());
            } else {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    $this->container->get(TranslatorInterface::class)->trans(
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
            $channel = $this->container->get(ManagerRegistry::class)
                ->getRepository(Channel::class)
                ->getOrLoadById($channelId);
        }

        return $channel;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ManagerRegistry::class,
                DotmailerResourcesFactory::class,
                TranslatorInterface::class,
                OAuthManager::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}
