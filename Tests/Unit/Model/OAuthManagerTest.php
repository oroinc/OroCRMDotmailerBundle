<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Http\Client\Common\HttpMethodsClientInterface;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Model\OAuthManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class OAuthManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encryptor;

    /** @var OAuthManager */
    private $oAuthManager;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);

        $curlClient = $this->createMock(HttpMethodsClientInterface::class);
        $curlClient->expects($this->any())
            ->method('post')
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->oAuthManager = new OAuthManager($this->router, $this->encryptor, $curlClient);
    }

    public function testGetApiEndpoint()
    {
        $transport = $this->createMock(DotmailerTransport::class);
        $transport->expects($this->once())
            ->method('getCustomDomain')
            ->willReturn(null);

        $expected = 'https://r1-app.dotmailer.com/';
        $actual = $this->oAuthManager->getApiEndpoint($transport);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAuthorizeUrl()
    {
        $transport = $this->createMock(DotmailerTransport::class);

        $expected = OAuthManager::AUTHORISE_URL;
        $actual = $this->oAuthManager->getAuthorizeUrl($transport);
        $this->assertEquals($expected, $actual);
    }

    public function testGetTokenUrl()
    {
        $transport = $this->createMock(DotmailerTransport::class);

        $expected = $this->oAuthManager->getApiEndpoint($transport) . 'OAuth2/Tokens.ashx';
        $actual = $this->oAuthManager->getTokenUrl($transport);
        $this->assertEquals($expected, $actual);
    }

    public function testGetLoginUserUrl()
    {
        $transport = $this->createMock(DotmailerTransport::class);

        $expected = $this->oAuthManager->getApiEndpoint($transport) . '?oauthtoken=';
        $actual = $this->oAuthManager->getLoginUserUrl($transport);
        $this->assertEquals($expected, $actual);
    }

    public function testGetCallbackUrl()
    {
        $expected = '/oauth/callback';

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_dotmailer_oauth_callback',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($expected);

        $actual = $this->oAuthManager->getCallbackUrl();
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateAuthorizeUrl()
    {
        $transport = $this->createMock(DotmailerTransport::class);
        $state = 'some string';

        $params = [
            'redirect_uri'  => $this->oAuthManager->getCallbackUrl(),
            'response_type' => 'code',
            'scope'         => 'Account',
            'state'         => $state
        ];
        $expected = $this->oAuthManager->getAuthorizeUrl($transport) .
            http_build_query($params) .
            '&client_id=' . $transport->getClientId();

        $actual = $this->oAuthManager->generateAuthorizeUrl($transport, $state);
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateRefreshToken()
    {
        $transport = $this->createMock(DotmailerTransport::class);
        $code = 'some string';

        $actual = $this->oAuthManager->generateRefreshToken($transport, $code);
        $this->assertFalse($actual);
    }

    public function testGenerateLoginUserUrl()
    {
        $transport = $this->createMock(DotmailerTransport::class);
        $refreshToken = 'some string';

        $actual = $this->oAuthManager->generateLoginUserUrl($transport, $refreshToken);
        $this->assertFalse($actual);
    }

    public function testGenerateAccessToken()
    {
        $transport = $this->createMock(DotmailerTransport::class);
        $refreshToken = 'some string';

        $actual = $this->oAuthManager->generateAccessToken($transport, $refreshToken);
        $this->assertFalse($actual);
    }
}
