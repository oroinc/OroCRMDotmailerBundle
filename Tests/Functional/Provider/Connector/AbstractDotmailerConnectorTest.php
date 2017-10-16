<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Provider\Connector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\CampaignConnector;

class AbstractDotmailerConnectorTest extends WebTestCase
{
    /** @var  AbstractDotmailerConnector */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMediator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $executionContext;

    protected function setUp()
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
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->any())
            ->method('getOption')
            ->with(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION)
            ->willReturn(null);
        $contextRegistry = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);

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
            $contextRegistry,
            $this->getContainer()
                ->get('oro_integration.logger.strategy'),
            $this->contextMediator
        );
        $connector->setManagerRegistry($this->getContainer()
            ->get('doctrine'));
        $this->executionContext = $this->createMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $stepExecution->expects($this->any())
            ->method('getExecutionContext')
            ->will($this->returnValue($this->executionContext));
        $connector->setStepExecution($stepExecution);
        return $connector;
    }
}
