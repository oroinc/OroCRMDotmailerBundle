<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;

/**
 * @dbIsolationPerTest
 */
class ContactsExportStatusUpdateCommandTest extends WebTestCase
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
        $result = $this->runCommand('oro:cron:dotmailer:export-status:update', ['--help']);

        self::assertContains("Usage:", $result);
        self::assertContains("oro:cron:dotmailer:export-status:update", $result);
    }

    public function testShouldSendExportContactStatusUpdatesToMessageQueue()
    {
        $result = $this->runCommand('oro:cron:dotmailer:export-status:update');

        $this->assertContains('Send export contacts status update for channel:', $result);
        $this->assertContains('Completed', $result);

        self::assertMessagesCount(Topics::EXPORT_CONTACTS_STATUS_UPDATE, 4);
    }
}
