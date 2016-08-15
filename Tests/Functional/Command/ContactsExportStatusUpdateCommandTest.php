<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroCRM\Bundle\DotmailerBundle\Async\Topics;
use OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;

/**
 * @dbIsolationPerTest
 */
class ContactsExportStatusUpdateCommandTest extends WebTestCase
{
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

        $traces = $this->getMessageProducer()->getTopicTraces(Topics::EXPORT_CONTACTS_STATUS_UPDATE);

        $this->assertCount(4, $traces);
    }

    /**
     * @return TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.message_producer');
    }
}
