<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\DotmailerBundle\Entity\OAuth;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OAuthTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new OAuth(), [
            ['id', 42],
            ['channel', new Channel()],
            ['user', new User()],
            ['refreshToken', 'some string']
        ]);
    }
}
