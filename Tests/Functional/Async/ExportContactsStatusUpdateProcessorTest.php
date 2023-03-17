<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Async;

use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ExportContactsStatusUpdateProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([
            LoadChannelData::class,
        ]);
    }

    public function testCouldBeGetFromContainerAsService(): void
    {
        $processor = self::getContainer()->get('oro_dotmailer.async.export_contacts_status_update_processor');

        self::assertInstanceOf(ExportContactsStatusUpdateProcessor::class, $processor);
    }

    public function testProcessIntegrationNotFound(): void
    {
        $sentMessage = self::sendMessage(
            ExportContactsStatusUpdateTopic::getName(),
            [
                'integrationId' => PHP_INT_MAX,
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_dotmailer.async.export_contacts_status_update_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasError('The integration not found: ' . PHP_INT_MAX)
        );
    }

    public function testProcessIntegrationNotActive(): void
    {
        $integrationId = $this->getReference('oro_dotmailer.channel.disabled.first')->getId();

        $sentMessage = self::sendMessage(
            ExportContactsStatusUpdateTopic::getName(),
            [
                'integrationId' => $integrationId,
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_dotmailer.async.export_contacts_status_update_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasError('The integration is not enabled: ' . $integrationId)
        );
    }

    public function testProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_dotmailer.channel.first');

        $sentMessage = self::sendMessage(
            ExportContactsStatusUpdateTopic::getName(),
            [
                'integrationId' => $integration->getId(),
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertMessageSentWithPriority(ExportContactsStatusUpdateTopic::getName(), MessagePriority::VERY_LOW);
        self::assertProcessedMessageProcessor(
            'oro_dotmailer.async.export_contacts_status_update_processor',
            $sentMessage
        );
    }
}
