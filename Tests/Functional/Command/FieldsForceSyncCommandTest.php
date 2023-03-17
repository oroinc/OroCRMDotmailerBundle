<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FieldsForceSyncCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:force-fields-sync', ['--help']);

        static::assertStringContainsString('Usage:', $result);
        static::assertStringContainsString('oro:cron:dotmailer:force-fields-sync', $result);
    }

    public function testRunCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:force-fields-sync');

        static::assertStringContainsString('Start update of address book contacts', $result);
        static::assertStringContainsString('Completed', $result);
    }
}
