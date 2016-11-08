<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
            ->getManager()
            ->getRepository('OroIntegrationBundle:Channel')
            ->findOneById($state);
        if ($channel) {
            $transport = $channel->getTransport();
            try {
                $refreshToken = $this->get('oro_dotmailer.oauth_helper')->generateRefreshToken($transport, $code);
            } catch (\Exception $exception) {
                $refreshToken = false;
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $exception->getMessage()
                );
            }
            if ($refreshToken) {
                $transport->setRefreshToken($refreshToken);

                $em = $this->get('doctrine')->getManager();
                $em->persist($transport);
                $em->flush($transport);
            }

            return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
        } else {
            return $this->redirectToRoute('oro_dotmailer_integration_connection_index');
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
        $transport = $channel->getTransport();
        $transport->setRefreshToken(null);

        $em = $this->get('doctrine')->getManager();
        $em->persist($transport);
        $em->flush($transport);

        return $this->redirectToRoute('oro_dotmailer_integration_connection', ['id' => $channel->getId()]);
    }
}
