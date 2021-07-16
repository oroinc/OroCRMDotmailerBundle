<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Http\Client\Common\HttpMethodsClientInterface;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This class provides ability to handle OAuth interaction with dotmailer API
 */
class OAuthManager
{
    const API_ENDPOINT   = 'https://r1-app.dotmailer.com/';
    const AUTHORISE_URL  = 'https://login.dotmailer.com/OAuth2/authorise.aspx?';
    const TOKEN_URL      = 'OAuth2/Tokens.ashx';
    const LOGIN_USER_URL = '?oauthtoken=';

    const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    const GRANT_TYPE_REFRESH_TOKEN      = 'refresh_token';

    const RETRY_TIMES = 3;

    /** @var RouterInterface */
    protected $router;

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var HttpMethodsClientInterface */
    protected $curlClient;

    public function __construct(
        RouterInterface $router,
        SymmetricCrypterInterface $encryptor,
        HttpMethodsClientInterface $curlClient
    ) {
        $this->router = $router;
        $this->encryptor = $encryptor;
        $this->curlClient = $curlClient;
    }

    /**
     * Returns API endpoint
     *
     * @param DotmailerTransport $transport
     *
     * @return string
     */
    public function getApiEndpoint(DotmailerTransport $transport)
    {
        return $transport->getCustomDomain() ?: self::API_ENDPOINT;
    }

    /**
     * Returns authorize URL
     *
     * @param DotmailerTransport $transport
     *
     * @return string
     */
    public function getAuthorizeUrl(DotmailerTransport $transport)
    {
        return self::AUTHORISE_URL;
    }

    /**
     * Returns token URL
     *
     * @param DotmailerTransport $transport
     *
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
     *
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
        return $this->router->generate(
            'oro_dotmailer_oauth_callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Generate authorize URL
     *
     * @param DotmailerTransport $transport
     * @param string             $state
     *
     * @return string|false
     */
    public function generateAuthorizeUrl(DotmailerTransport $transport, $state)
    {
        $params = [
            'redirect_uri'  => $this->getCallbackUrl(),
            'response_type' => 'code',
            'scope'         => 'Account',
            'state'         => $state
        ];

        return
            $this->getAuthorizeUrl($transport)
            . http_build_query($params)
            . '&client_id=' . $transport->getClientId();
    }

    /**
     * Generate refresh token
     *
     * @param DotmailerTransport $transport
     * @param string             $code
     *
     * @return string|false
     */
    public function generateRefreshToken(DotmailerTransport $transport, $code)
    {
        $url = $this->getTokenUrl($transport);
        $params = [
            'client_id'     => $transport->getClientId(),
            'client_secret' => $this->encryptor->decryptData($transport->getClientKey()),
            'redirect_uri'  => $this->getCallbackUrl(),
            'grant_type'    => self::GRANT_TYPE_AUTHORIZATION_CODE,
            'code'          => $code
        ];

        $attemptNumber = 0;
        do {
            $attemptNumber++;
            $response = $this->doCurlRequest($url, $params);
            $token = empty($response['refresh_token']) ? false : $response['refresh_token'];
        } while ($attemptNumber <= self::RETRY_TIMES && !$token);

        return $token;
    }

    /**
     * Generate login user URL
     *
     * @param DotmailerTransport $transport
     * @param string             $refreshToken
     *
     * @return string|false
     */
    public function generateLoginUserUrl(DotmailerTransport $transport, $refreshToken)
    {
        $token = $this->generateAccessToken($transport, $refreshToken);
        if (!$token) {
            return false;
        }

        return $this->getLoginUserUrl($transport) . $token;
    }

    /**
     * Generate token
     *
     * @param DotmailerTransport $transport
     * @param string             $refreshToken
     *
     * @return string|false
     */
    public function generateAccessToken(DotmailerTransport $transport, $refreshToken)
    {
        $url = $this->getTokenUrl($transport);
        $params = [
            'client_id'     => $transport->getClientId(),
            'client_secret' => $this->encryptor->decryptData($transport->getClientKey()),
            'refresh_token' => $refreshToken,
            'grant_type'    => self::GRANT_TYPE_REFRESH_TOKEN
        ];

        $attemptNumber = 0;
        do {
            $attemptNumber++;
            $response = $this->doCurlRequest($url, $params);
            $token = empty($response['access_token']) ? false : $response['access_token'];
        } while ($attemptNumber <= self::RETRY_TIMES && !$token);

        return $token;
    }

    /**
     * Perform a cUrl request
     *
     * @param string $url
     * @param array  $params
     *
     * @return array
     */
    protected function doCurlRequest($url, $params)
    {
        $content = http_build_query($params, '', '&');
        $headers = [
            'Content-length: ' . strlen($content),
            'content-type: application/x-www-form-urlencoded',
            'user-agent: oro-oauth'
        ];

        $response = $this->curlClient->post($url, $headers, $content);
        $responseContent = $this->getResponseContent($response);

        if (isset($responseContent['error_description'])) {
            throw new RuntimeException($responseContent['error_description']);
        }
        if (isset($responseContent['error'])) {
            throw new RuntimeException($responseContent['error']);
        }

        return $responseContent;
    }

    /**
     * Get the 'parsed' content based on the response headers
     *
     * @param MessageInterface $response
     *
     * @return array
     */
    protected function getResponseContent(MessageInterface $response)
    {
        $content = $response->getBody();
        if (!$content) {
            return [];
        }

        return json_decode($content, true);
    }
}
