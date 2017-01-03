<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Entity\OAuth;

/**
 * @Route("/oauth")
 */
class OauthController extends Controller
{
    /**
     * @Route(
     *      "/callback",
     *      name="orocrm_dotmailer_oauth_callback"
     * )
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
            ->getOrLoadById($state);
        if ($channel) {
            $transport = $channel->getTransport();
            $refreshToken = false;
            try {
                $refreshToken = $this->get('orocrm_dotmailer.oauth_manager')->generateRefreshToken($transport, $code);
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
            if ($refreshToken) {
                $oauth = $this->getDoctrine()
                    ->getRepository('OroCRMDotmailerBundle::OAuth')
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

            return $this->redirectToRoute('orocrm_dotmailer_integration_connection', ['id' => $channel->getId()]);
        } else {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('orocrm.dotmailer.integration.messsage.incorrect_callback_url')
            );

            return $this->redirectToRoute('orocrm_dotmailer_integration_connection');
        }
    }

    /**
     * @Route(
     *      "/disconnect/{id}",
     *      name="orocrm_dotmailer_oauth_disconnect",
     *      requirements={"id"="\d+"}
     * )
     *
     * @param Channel $channel
     * @return RedirectResponse
     */
    public function disconnectAction(Channel $channel)
    {
        $oauth = $this->getDoctrine()
            ->getRepository('OroCRMDotmailerBundle::OAuth')
            ->findByChannelAndUser($channel, $this->getUser());
        if ($oauth) {
            $em = $this->get('doctrine')->getManager();
            $em->remove($oauth);
            $em->flush();
        }

        return $this->redirectToRoute('orocrm_dotmailer_integration_connection', ['id' => $channel->getId()]);
    }
}
