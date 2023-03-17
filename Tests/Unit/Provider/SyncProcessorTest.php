<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Psr\Log\NullLogger;

class SyncProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobProcessor;

    /** @var AbstractSyncProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedSyncProcessor;

    /** @var SyncProcessor */
    private $syncProcessor;

    protected function setUp(): void
    {
        $this->jobProcessor = $this->createMock(JobProcessor::class);
        $this->decoratedSyncProcessor = $this->createMock(AbstractSyncProcessor::class);

        $this->syncProcessor = new SyncProcessor($this->jobProcessor, $this->decoratedSyncProcessor);
    }

    public function testShouldThrowAnExceptionForOtherIntegraiton()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong integration type, "dotmailer" expected, "other" given');

        $integration = new Channel();
        $integration->setType('other');

        $this->jobProcessor->expects($this->never())
            ->method('findRootJobByJobNameAndStatuses');
        $this->decoratedSyncProcessor->expects($this->never())
            ->method('process');

        $this->syncProcessor->process($integration, 'connector');
    }

    public function testShouldSkipProcessingWhenJobFound()
    {
        $integration = new Channel();
        $integration->setType(ChannelType::TYPE);

        $this->jobProcessor->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn(new Job());

        $this->decoratedSyncProcessor->expects($this->never())
            ->method('process');

        $this->assertTrue(
            $this->syncProcessor->process($integration, 'connector')
        );
    }

    public function testShouldProcessingWhenNoJobFound()
    {
        $integration = new Channel();
        $integration->setType(ChannelType::TYPE);

        $this->jobProcessor->expects($this->once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn(null);

        $this->decoratedSyncProcessor->expects($this->once())
            ->method('process')
            ->willReturn(true);

        $this->assertTrue(
            $this->syncProcessor->process($integration, 'connector')
        );
    }

    public function testGetLoggerStrategy()
    {
        $loggerStrategy = new LoggerStrategy();
        $this->decoratedSyncProcessor->expects($this->once())
            ->method('getLoggerStrategy')
            ->willReturn($loggerStrategy);

        $this->assertSame(
            $loggerStrategy,
            $this->syncProcessor->getLoggerStrategy()
        );
    }

    public function testGetLoggerStrategyEmpty()
    {
        $this->decoratedSyncProcessor = $this->createMock(SyncProcessorInterface::class);

        $this->syncProcessor = new SyncProcessor($this->jobProcessor, $this->decoratedSyncProcessor);

        $this->assertInstanceOf(
            NullLogger::class,
            $this->syncProcessor->getLoggerStrategy()
        );
    }
}
