<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Utils;

use Oro\Bundle\DotmailerBundle\Utils\EmailUtils;

class EmailUtilsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetLowerCaseEmails()
    {
        $emails = [
            'Test1@Example.Com',
            'TEST2@EXAMPLE.COM',
            'test3@example.com',
        ];

        $this->assertEquals([
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
        ], EmailUtils::getLowerCaseEmails($emails));
    }

    /**
     * @dataProvider emailDataProvider
     */
    public function testGetLowerCaseEmail(string $email, string $expectedEmail)
    {
        $this->assertEquals($expectedEmail, EmailUtils::getLowerCaseEmail($email));
    }

    public function emailDataProvider(): array
    {
        return [
            'camelcase' => [
                'email' => 'Test1@Example.Com',
                'expectedEmail' => 'test1@example.com',
            ],
            'uppercase' => [
                'email' => 'TEST2@EXAMPLE.COM',
                'expectedEmail' => 'test2@example.com',
            ],
            'lowercase' => [
                'email' => 'test3@example.com',
                'expectedEmail' => 'test3@example.com',
            ],
        ];
    }
}
