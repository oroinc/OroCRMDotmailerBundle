<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OauthControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge(self::generateBasicAuthHeader(), ['HTTPS' => true]));
        $this->client->useHashNavigation(true);
    }

    public function testCallbackActionWhenNoStatusParameter()
    {
        $this->client->request('GET', $this->getUrl('oro_dotmailer_oauth_callback'));
        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 403);
    }
}
