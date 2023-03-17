<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use DotMailer\Api\Resources\IResources;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    protected const RESOURCES_FACTORY_ID = 'oro_dotmailer.transport.resources_factory.stub';
    protected const SYNC_PROCESSOR = 'oro_integration.sync.processor';

    /** @var IResources|\PHPUnit\Framework\MockObject\MockObject */
    protected $resource;

    /** @var DotmailerResourcesFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $resourceFactory;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');

        $this->stubResources();

        $this->managerRegistry = $this->getContainer()
            ->get('doctrine');
    }

    protected function runImportExportConnectorsJob(
        string $processorId,
        Channel $channel,
        string $connector,
        array $parameters = [],
        ?array &$jobLog = []
    ): bool {
        /** @var SyncProcessor $processor */
        $processor = $this->getContainer()->get($processorId);
        $testLoggerHandler = new TestHandler(Logger::WARNING);
        $processor->getLoggerStrategy()->setLogger(new Logger('testDebug', [$testLoggerHandler]));

        $result = $processor->process($channel, $connector, $parameters);

        $jobLog = $testLoggerHandler->getRecords();

        return $result;
    }

    protected function formatImportExportJobLog(array $jobLog): ?string
    {
        return array_reduce(
            $jobLog,
            function ($carry, $record) {
                return $carry . sprintf(
                    '%s> [level: %s] Message: %s',
                    PHP_EOL,
                    $record['level_name'],
                    empty($record['formatted']) ? $record['message'] : $record['formatted']
                );
            }
        );
    }

    protected function stubResources()
    {
        if ($this->getContainer()->initialized(self::RESOURCES_FACTORY_ID)) {
            return;
        }

        $this->resource = $this->createMock(IResources::class);

        $this->resourceFactory = $this->createMock(DotmailerResourcesFactory::class);
        $this->resourceFactory->expects($this->any())
            ->method('createResources')
            ->willReturn($this->resource);

        $this->getContainer()
            ->set(self::RESOURCES_FACTORY_ID, $this->resourceFactory);
    }
}
