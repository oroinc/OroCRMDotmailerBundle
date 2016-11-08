<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class OauthHelper
{
    const API_ENDPOINT   = 'https://r1-app.dotmailer.com/';
    const AUTHORISE_URL  = 'OAuth2/authorise.aspx?';
    const TOKEN_URL      = 'OAuth2/Tokens.ashx';
    const LOGIN_USER_URL = '?oauthtoken=';

    /** @var RouterInterface */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Returns API endpoint
     *
     * @param DotmailerTransport $transport
     * @return string
     */
    public function getApiEndpoint(DotmailerTransport $transport)
    {
        return $transport->getCustomDomain() ? $transport->getCustomDomain() : self::API_ENDPOINT;
    }

    /**
     * Returns authorize URL
     *
     * @param DotmailerTransport $transport
     * @return string
     */
    public function getAuthorizeUrl(DotmailerTransport $transport)
    {
        return $this->getApiEndpoint($transport) . self::AUTHORISE_URL;
    }

    /**
     * Returns token URL
     *
     * @param DotmailerTransport $transport
     * @return string
     */
    public function getTokenUrl(DotmailerTransport $transport)
    {
        return $this->getApiEndpoint($transport) . self::TOKEN_URL;
    }

    /**
     * Returns login user URL
     *
     * @param DotmailerTransport $transport
     * @return string
     */
    public function getLoginUserUrl(DotmailerTransport $transport)
    {
        return $this->getApiEndpoint($transport) . self::LOGIN_USER_URL;
    }

    /**
     * Returns callback URL
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        $callbackUrl = $this->router->generate(
            'oro_dotmailer_oauth_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $callbackUrl;
    }

    /**
     * Generate authorize URL
     *
     * @param DotmailerTransport $transport
     * @param string $state
     * @return string|false
     */
    public function generateAuthorizeUrl(DotmailerTransport $transport, $state)
    {
        $params = array(
            'redirect_uri'  => $this->getCallbackUrl(),
            'response_type' => 'code',
            'scope'         => 'Account',
            'state'         => $state
        );
        $authorizeUrl = $this->getAuthorizeUrl($transport) .
            http_build_query($params) .
            '&client_id=' . $transport->getClientId();

        return $authorizeUrl;
    }

    /**
     * Generate refresh token
     *
     * @param DotmailerTransport $transport
     * @param string $code
     * @return string|false
     */
    public function generateRefreshToken(DotmailerTransport $transport, $code)
    {
        $url = $this->getTokenUrl($transport);
        $data = 'client_id=' . $transport->getClientId() .
            '&client_secret=' . $transport->getClientKey() .
            '&redirect_uri=' . $this->getCallbackUrl() .
            '&grant_type=authorization_code' .
            '&code=' . $code;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        $response = curl_exec($ch);
        if ($response === false) {
            throw new RuntimeException('cURL error #' . curl_errno($ch) . ': ' . curl_error($ch));
        } else {
            $result = json_decode($response);
            if (isset($result->error_description)) {
                throw new RuntimeException($result->error_description);
            } elseif (isset($result->error)) {
                throw new RuntimeException($result->error);
            }
        }
        curl_close($ch);

        $token = isset($result->refresh_token) ? $result->refresh_token : false;

        return $token;
    }

    /**
     * Generate login user URL
     *
     * @param DotmailerTransport $transport
     * @return string|false
     */
    public function generateLoginUserUrl(DotmailerTransport $transport)
    {
        $token = $this->generateAccessToken($transport);
        if (!$token) {
            return false;
        }

        $loginUserUrl = $this->getLoginUserUrl($transport) . $token . '&suppressfooter=true';

        return $loginUserUrl;
    }

    /**
     * Generate token
     *
     * @param DotmailerTransport $transport
     * @return string|false
     */
    public function generateAccessToken(DotmailerTransport $transport)
    {
        $refreshToken = $transport->getRefreshToken();
        if (!$refreshToken) {
            return false;
        }

        $url = $this->getTokenUrl($transport);
        $params = 'client_id=' . $transport->getClientId() .
            '&client_secret=' . $transport->getClientKey() .
            '&refresh_token=' . $refreshToken .
            '&grant_type=refresh_token';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        $response = curl_exec($ch);
        if ($response === false) {
            throw new RuntimeException('cURL error #' . curl_errno($ch) . ': ' . curl_error($ch));
        } else {
            $result = json_decode($response);
            if (isset($result->error_description)) {
                throw new RuntimeException($result->error_description);
            } elseif (isset($result->error)) {
                throw new RuntimeException($result->error);
            }
        }
        curl_close($ch);

        $token = isset($result->access_token) ? $result->access_token : false;

        return $token;
    }
}
