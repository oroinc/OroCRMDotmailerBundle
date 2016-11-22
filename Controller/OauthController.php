<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\OAuth;

/**
 * @Route("/oauth")
 */
class OauthController extends Controller
{
    /**
     * @Route(
     *      "/callback",
     *      name="oro_dotmailer_oauth_callback"
     * )
     * @AclAncestor("oro_dotmailer")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function callbackAction(Request $request)
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        $channel = $this->getDoctrine()
            ->getRepository('OroIntegrationBundle:Channel')
            ->findOneById($state);
        if ($channel) {
            $transport = $channel->getTransport();
            try {
                $refreshToken = $this->get('oro_dotmailer.oauth_manager')->generateRefreshToken($transport, $code);
            } catch (\Exception $exception) {
                $refreshToken = false;
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $exception->getMessage()
                );
            }
            if ($refreshToken) {
                $oauth = $this->getDoctrine()
                    ->getRepository('OroDotmailerBundle::OAuth')
                    ->findByChannelAndUser($channel, $this->getUser());
                if (!$oauth) {
                    $oauth = new OAuth();
                    $oauth->setChannel($channel)
                        ->setUser($this->getUser());
                }
                $oauth->setRefreshToken($refreshToken);

                $em = $this->get('doctrine')->getManager();
                $em->persist($oauth);
                $em->flush($oauth);
            }

            return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
        } else {
            return $this->redirectToRoute('oro_dotmailer_integration_connection');
        }
    }

    /**
     * @Route(
     *      "/disconnect/{id}",
     *      name="oro_dotmailer_oauth_disconnect",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_dotmailer")
     *
     * @param Channel $channel
     * @return RedirectResponse
     */
    public function disconnectAction(Channel $channel)
    {
        $oauth = $this->getDoctrine()
            ->getRepository('OroDotmailerBundle::OAuth')
            ->findByChannelAndUser($channel, $this->getUser());
        if ($oauth) {
            $em = $this->get('doctrine')->getManager();
            $em->remove($oauth);
            $em->flush();
        }

        return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
    }
}
