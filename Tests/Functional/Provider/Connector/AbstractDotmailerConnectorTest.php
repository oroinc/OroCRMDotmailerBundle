<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Provider\Connector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\CampaignsConnector;

/**
 * @dbIsolation
 * @dbReindex
 */
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
    protected $stepExecution;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadStatusData'
            ]
        );
    }

    public function testGetLastSyncDate()
    {
        $connector = $this->getConnector('orocrm_dotmailer.channel.second');
        $context = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $this->stepExecution->expects($this->once())
            ->method('getExecutionContext')
            ->will($this->returnValue($context));

        $date = $connector->getLastSyncDate();
        $this->assertInstanceOf('\DateTime', $date);

        $this->assertEquals('2015-10-10', $date->format('Y-m-d'));
    }


    public function testGetLastSyncDateReturnNullForFirstSync()
    {
        $connector = $this->getConnector('orocrm_dotmailer.channel.first');
        $context = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $this->stepExecution->expects($this->once())
            ->method('getExecutionContext')
            ->will($this->returnValue($context));

        $date = $connector->getLastSyncDate();
        $this->assertNull($date);
    }

    /**
     * @param string $channel
     * @return CampaignsConnector
     */
    protected function getConnector($channel)
    {
        $this->contextMediator = $this->getMockBuilder(
            'Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
        $this->contextMediator->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->stepExecution = $this->getMockBuilder(
            'Akeneo\Bundle\BatchBundle\Entity\StepExecution'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->getReference($channel)));

        $connector = new CampaignsConnector(
            $this->getContainer()
                ->get('oro_importexport.context_registry'),
            $this->getContainer()
                ->get('oro_integration.logger.strategy'),
            $this->contextMediator
        );
        $connector->setManagerRegistry($this->getContainer()
            ->get('doctrine'));
        $connector->setStepExecution($this->stepExecution);
        return $connector;
    }
}
