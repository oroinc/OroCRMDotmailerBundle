<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobProcessor;

/**
 * @dbIsolationPerTest
 */
class ContactsExportStatusUpdateCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testShouldOutputHelpForTheCommand(): void
    {
        $result = self::runCommand('oro:cron:dotmailer:export-status:update', ['--help']);

        self::assertStringContainsString('Usage:', $result);
        self::assertStringContainsString('oro:cron:dotmailer:export-status:update', $result);
    }

    public function testShouldSendExportContactStatusUpdatesToMessageQueue(): void
    {
        $result = self::runCommand('oro:cron:dotmailer:export-status:update');

        self::assertStringContainsString('Send export contacts status update for integration:', $result);
        self::assertStringContainsString('Completed', $result);

        self::assertMessagesCount(ExportContactsStatusUpdateTopic::getName(), 4);
    }

    public function testShouldSkipWhenIntegrationSyncInProgress(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_dotmailer.channel.first');

        /** @var JobProcessor $jobProcessor */
        $jobProcessor = self::getContainer()->get('oro_message_queue.job.processor');
        $job = $jobProcessor->findOrCreateRootJob(
            uniqid('dm', true),
            'oro_integration:sync_integration:'.$integration->getId(),
            true
        );

        self::assertNotNull($job->getId());

        $result = self::runCommand('oro:cron:dotmailer:export-status:update');

        self::assertStringContainsString('Send export contacts status update for integration:', $result);
        self::assertStringContainsString(
            sprintf('Skip "%s" integration because integration job already exists', $integration->getName()),
            $result
        );
        self::assertStringContainsString('Completed', $result);

        self::assertMessagesCount(ExportContactsStatusUpdateTopic::getName(), 3);
    }
}
