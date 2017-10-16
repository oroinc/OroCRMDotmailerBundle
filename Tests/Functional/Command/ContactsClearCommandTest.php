<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ContactsClearCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testShouldOutputHelpForTheCommand()
    {
        $result = $this->runCommand('oro:cron:dotmailer:contacts:clear', ['--help']);

        self::assertContains("Usage:", $result);
        self::assertContains("oro:cron:dotmailer:contacts:clear", $result);
    }

    public function testShouldSendContactClearToMessageQueue()
    {
        $result = $this->runCommand('oro:cron:dotmailer:contacts:clear');

        $this->assertContains('Send contacts clear for integration:', $result);
        $this->assertContains('Completed', $result);

        self::assertMessagesCount(Topics::DM_CONTACTS_CLEANER, 4);
    }
}
