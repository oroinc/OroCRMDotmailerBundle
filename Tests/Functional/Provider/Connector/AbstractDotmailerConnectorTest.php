<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Provider\Connector;

use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AbstractDotmailerConnectorTest extends WebTestCase
{
    /** @var  AbstractDotmailerConnector */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMediator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData'
            ]
        );
    }

    public function testGetLastSyncDate()
    {
        $connector = $this->getConnector('oro_dotmailer.channel.second');
        $date = $connector->getLastSyncDate();
        $this->assertInstanceOf('\DateTime', $date);

        $this->assertEquals('2015-10-10', $date->format('Y-m-d'));
    }

    public function testGetLastSyncDateReturnNullForFirstSync()
    {
        $connector = $this->getConnector('oro_dotmailer.channel.first');
        $date = $connector->getLastSyncDate();
        $this->assertNull($date);
    }

    /**
     * @param string $channel
     * @return CampaignConnector
     */
    protected function getConnector($channel)
    {
        $this->contextMediator = $this->getMockBuilder(
            'Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transport = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\TransportInterface')
            ->setMethods(['init', 'getLabel', 'getSettingsFormType', 'getSettingsEntityFQCN', 'getCampaigns'])
            ->getMock();
        $iterator = $this->createMock('\Iterator');
        $transport->expects($this->any())
            ->method('getCampaigns')
            ->will($this->returnValue($iterator));
        $this->contextMediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $stepExecution = $this->getMockBuilder(
            'Akeneo\Bundle\BatchBundle\Entity\StepExecution'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->getReference($channel)));

        $connector = new CampaignConnector(
            $this->getContainer()
                ->get('oro_importexport.context_registry'),
            $this->getContainer()
                ->get('oro_integration.logger.strategy'),
            $this->contextMediator
        );
        $connector->setManagerRegistry($this->getContainer()
            ->get('doctrine'));
        $this->context = $this->createMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $stepExecution->expects($this->any())
            ->method('getExecutionContext')
            ->will($this->returnValue($this->context));
        $connector->setStepExecution($stepExecution);
        return $connector;
    }
}
