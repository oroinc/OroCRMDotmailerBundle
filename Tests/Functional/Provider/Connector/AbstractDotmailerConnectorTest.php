<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Provider\Connector;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AbstractDotmailerConnectorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStatusData::class]);
    }

    public function testGetLastSyncDate()
    {
        $connector = $this->getConnector('oro_dotmailer.channel.second');
        $date = $connector->getLastSyncDate();
        $this->assertInstanceOf(\DateTime::class, $date);

        $this->assertEquals('2015-10-10', $date->format('Y-m-d'));
    }

    public function testGetLastSyncDateReturnNullForFirstSync()
    {
        $connector = $this->getConnector('oro_dotmailer.channel.first');
        $date = $connector->getLastSyncDate();
        $this->assertNull($date);
    }

    private function getConnector(string $channel): CampaignConnector
    {
        $contextMediator = $this->createMock(ConnectorContextMediator::class);
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->onlyMethods(['init', 'getLabel', 'getSettingsFormType', 'getSettingsEntityFQCN'])
            ->addMethods(['getCampaigns'])
            ->getMock();
        $iterator = $this->createMock(\Iterator::class);
        $transport->expects($this->any())
            ->method('getCampaigns')
            ->willReturn($iterator);
        $contextMediator->expects($this->any())
            ->method('getTransport')
            ->willReturn($transport);

        $contextMediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($this->getReference($channel));

        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($this->createMock(ExecutionContext::class));

        $connector = new CampaignConnector(
            $this->getContainer()->get('oro_importexport.context_registry'),
            $this->getContainer()->get('oro_integration.logger.strategy'),
            $contextMediator
        );
        $connector->setManagerRegistry($this->getContainer()
            ->get('doctrine'));
        $connector->setStepExecution($stepExecution);

        return $connector;
    }
}
