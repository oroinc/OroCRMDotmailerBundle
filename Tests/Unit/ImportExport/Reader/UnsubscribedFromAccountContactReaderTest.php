<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\ImportExport\Reader;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Reader\UnsubscribedFromAccountContactReader;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;

class UnsubscribedFromAccountContactReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnsubscribedFromAccountContactReader
     */
    protected $reader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMediator;

    protected function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->logger = $this->getMock('\Psr\Log\LoggerInterface');

        $this->reader = new UnsubscribedFromAccountContactReader(
            $this->contextRegistry,
            $this->contextMediator,
            $this->managerRegistry,
            $this->logger
        );
    }

    /**
     * @dataProvider readerDataProvider
     *
     * @param array $data
     * @param \DateTime|null $expectedDate
     */
    public function testReader(array $data, $expectedDate)
    {
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');

        $iterator = $this->getMock('\Iterator');

        $transport = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $transport->expects($this->once())
            ->method('getUnsubscribedFromAccountsContacts')
            ->with($expectedDate)
            ->willReturn($iterator);

        $this->setupReaderDependenciesStubs($data, $channel, $transport);
        $this->reader->read();

        $this->assertEquals($iterator, $this->reader->getSourceIterator());
    }

    public function readerDataProvider()
    {
        return [
            'initial sync' => [
                'data' => [
                    'contactConnectorStatus' => [
                        'data' => [
                            AbstractDotmailerConnector::LAST_SYNC_DATE_KEY => '2015-02-02 00:00:00',
                        ]
                    ]
                ],
                'expectedDate' => date_create_from_format(
                    'Y-m-d H:i:s',
                    '2015-02-02 00:00:00',
                    new \DateTimeZone('UTC')
                )
            ],
            'second sync' => [
                'data' => [
                    'contactConnectorStatus' => [
                        'data' => [
                            AbstractDotmailerConnector::LAST_SYNC_DATE_KEY => '2015-02-02 00:00:00',
                        ]
                    ],
                    'unsubscribedContactConnectorStatus' => [
                        'data' => [
                            AbstractDotmailerConnector::LAST_SYNC_DATE_KEY => '2015-03-03 00:00:00',
                        ]
                    ],
                ],
                'expectedDate' => date_create_from_format(
                    'Y-m-d H:i:s',
                    '2015-03-03 00:00:00',
                    new \DateTimeZone('UTC')
                )
            ],
            'second sync without date' => [
                'data' => [
                    'contactConnectorStatus' => [
                        'data' => [
                            AbstractDotmailerConnector::LAST_SYNC_DATE_KEY => '2015-02-02 00:00:00',
                        ]
                    ],
                    'unsubscribedContactConnectorStatus' => [
                        'data' => []
                    ],
                ],
                'expectedDate' => date_create_from_format(
                    'Y-m-d H:i:s',
                    '2015-02-02 00:00:00',
                    new \DateTimeZone('UTC')
                )
            ]
        ];
    }

    /**
     * @param array                                    $data
     * @param \PHPUnit_Framework_MockObject_MockObject $channel
     * @param \PHPUnit_Framework_MockObject_MockObject $transport
     */
    protected function setupReaderDependenciesStubs(array $data, $channel, $transport)
    {
        $statusRepositoryMap = [];
        $statusRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        if (!empty($data['unsubscribedContactConnectorStatus'])) {
            $status = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Status');

            $status->expects($this->any())
                ->method('getData')
                ->willReturn($data['unsubscribedContactConnectorStatus']['data']);
            $statusRepositoryMap[] = [
                [
                    'code'      => Status::STATUS_COMPLETED,
                    'channel'   => $channel,
                    'connector' => UnsubscribedContactConnector::TYPE
                ],
                [
                    'date' => 'DESC'
                ],
                $status
            ];
        }
        if (!empty($data['contactConnectorStatus'])) {
            $status = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Status');

            $status->expects($this->any())
                ->method('getData')
                ->willReturn($data['contactConnectorStatus']['data']);
            $statusRepositoryMap[] = [
                [
                    'code'      => Status::STATUS_COMPLETED,
                    'channel'   => $channel,
                    'connector' => ContactConnector::TYPE
                ],
                [
                    'date' => 'DESC'
                ],
                $status
            ];
        }

        $statusRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnMap($statusRepositoryMap);

        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroIntegrationBundle:Status', null, $statusRepository]
                ]
            );
        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($channel);

        $this->contextMediator->expects($this->any())
            ->method('getInitializedTransport')
            ->with($channel)
            ->willReturn($transport);
        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $jobExecution->expects($this->any())
            ->method('getStepExecutions')
            ->willReturn([]);
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);
    }
}
