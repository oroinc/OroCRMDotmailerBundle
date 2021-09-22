<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Oro\Bundle\DotmailerBundle\Entity\OAuth;
use Oro\Bundle\DotmailerBundle\Exception\BadRequestException;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Model\OAuthManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves dotmailer OAuth actions.
 * @Route("/oauth")
 */
class OauthController extends AbstractController
{
    /**
     * @Route(
     *      "/callback",
     *      name="oro_dotmailer_oauth_callback"
     * )
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function callbackAction(Request $request)
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        if (!$state) {
            throw new BadRequestException('The request does not contain a state parameter.');
        }

        $channel = $this->getDoctrine()
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($state);

        $translator = $this->get(TranslatorInterface::class);

        if ($channel) {
            $transport = $channel->getTransport();
            $refreshToken = false;
            try {
                $refreshToken = $this->get(OAuthManager::class)->generateRefreshToken($transport, $code);
            } catch (RuntimeException $e) {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    $e->getMessage()
                );
            } catch (\Exception $e) {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    $translator->trans('oro.dotmailer.integration.messsage.unable_to_connect')
                );
            }
            if ($refreshToken) {
                $oauth = $this->getDoctrine()
                    ->getRepository('OroDotmailerBundle:OAuth')
                    ->findByChannelAndUser($channel, $this->getUser());
                if (!$oauth) {
                    $oauth = new OAuth();
                    $oauth->setChannel($channel)
                        ->setUser($this->getUser());
                }
                $oauth->setRefreshToken($refreshToken);

                $em = $this->get('doctrine')->getManager();
                $em->persist($oauth);
                $em->flush();
            }

            return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
        } else {
            $request->getSession()->getFlashBag()->add(
                'error',
                $translator->trans('oro.dotmailer.integration.messsage.incorrect_callback_url')
            );

            return $this->redirectToRoute('oro_dotmailer_integration_connection');
        }
    }

    /**
     * @Route(
     *      "/disconnect/{id}",
     *      name="oro_dotmailer_oauth_disconnect",
     *      requirements={"id"="\d+"}
     * )
     *
     * @param Channel $channel
     * @return RedirectResponse
     */
    public function disconnectAction(Channel $channel)
    {
        $oauth = $this->getDoctrine()
            ->getRepository('OroDotmailerBundle:OAuth')
            ->findByChannelAndUser($channel, $this->getUser());
        if ($oauth) {
            $em = $this->get('doctrine')->getManager();
            $em->remove($oauth);
            $em->flush();
        }

        return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                OAuthManager::class,
            ]
        );
    }
}
