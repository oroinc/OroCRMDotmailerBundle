<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    const RESOURCES_FACTORY_ID = 'orocrm_dotmailer.transport.resources_factory';
    const SYNC_PROCESSOR = 'oro_integration.sync.processor';

    /**
     * @var DotmailerResourcesFactory
     */
    protected $oldResourceFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    protected function setUp()
    {
        $this->initClient();
        $this->stubResources();
        $this->managerRegistry = $this->getContainer()
            ->get('doctrine');

        $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
    }

    public function tearDown()
    {
        $this->getContainer()->set(self::RESOURCES_FACTORY_ID, $this->oldResourceFactory);

        // clear DB from separate connection, close to avoid connection limit and memory leak
        $manager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $manager->rollback();
        $manager->getConnection()->close();

        parent::tearDown();
    }

    /**
     * @param string  $processorId
     *
     * @param Channel $channel
     * @param string  $connector
     * @param array   $parameters
     * @param array   $jobLog
     *
     * @return bool
     */
    public function runImportExportConnectorsJob(
        $processorId,
        Channel $channel,
        $connector,
        array $parameters = [],
        &$jobLog = []
    ) {
        /** @var SyncProcessor $processor */
        $processor = $this->getContainer()->get($processorId);
        $testLoggerHandler = new TestHandler(Logger::WARNING);
        $processor->getLoggerStrategy()->setLogger(new Logger('testDebug', [$testLoggerHandler]));

        $result = $processor->process($channel, $connector, $parameters);

        $jobLog = $testLoggerHandler->getRecords();

        return $result;
    }

    /**
     * @param array $jobLog
     *
     * @return string
     */
    public function formatImportExportJobLog(array $jobLog)
    {
        $output = array_reduce(
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

        return $output;
    }

    protected function stubResources()
    {
        $this->resource = $this->getMock('DotMailer\Api\Resources\IResources');
        $resourceFactory = $this->getMock('OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerResourcesFactory');
        $resourceFactory->expects($this->any())
            ->method('createResources')
            ->will($this->returnValue($this->resource));

        $this->oldResourceFactory = $this->getContainer()
            ->get(self::RESOURCES_FACTORY_ID);
        $this->getContainer()
            ->set(self::RESOURCES_FACTORY_ID, $resourceFactory);
    }
}
