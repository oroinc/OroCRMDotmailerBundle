<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProcessMappedFieldsUpdatesCommandTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process', ['--help']);

        self::assertContains('Usage:', $result);
        self::assertContains('oro:cron:dotmailer:mapped-fields-updates:process', $result);
    }

    public function testShouldSendExportContactStatusUpdatesToMessageQueue()
    {
        $result = $this->runCommand('oro:cron:dotmailer:mapped-fields-updates:process');

        $this->assertContains('Start queue processing', $result);
        $this->assertContains('Completed', $result);
    }
}
