<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\DotmailerBundle\Entity\OAuth;

class OAuthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OAuth
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new OAuth();
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    public function flatPropertiesDataProvider()
    {
        $channel = new Channel();
        $user = new User();
        $refreshToken = uniqid();

        return array(
            'channel' => array('channel', $channel, $channel),
            'user' => array('user', $user, $user),
            'refreshToken' => array('refreshToken', $refreshToken, $refreshToken)
        );
    }

    public function testIdWorks()
    {
        $this->assertEmpty($this->entity->getId());
    }
}
