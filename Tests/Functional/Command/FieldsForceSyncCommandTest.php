<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FieldsForceSyncCommandTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:force-fields-sync', ['--help']);

        self::assertContains('Usage:', $result);
        self::assertContains('oro:cron:dotmailer:force-fields-sync', $result);
    }

    public function testRunCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:force-fields-sync');

        $this->assertContains('Start update of address book contacts', $result);
        $this->assertContains('Completed', $result);
    }
}
