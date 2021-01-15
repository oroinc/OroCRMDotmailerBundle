<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport\Reader;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\UnsubscribedFromAccountContactReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\IntegrationBundle\Entity\Status;

class UnsubscribedFromAccountContactReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UnsubscribedFromAccountContactReader
     */
    protected $reader;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMediator;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->logger = $this->createMock('\Psr\Log\LoggerInterface');

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
        $channel = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');

        $iterator = $this->createMock('\Iterator');

        $transport = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport')
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
     * @param \PHPUnit\Framework\MockObject\MockObject $channel
     * @param \PHPUnit\Framework\MockObject\MockObject $transport
     */
    protected function setupReaderDependenciesStubs(array $data, $channel, $transport)
    {
        $statusRepositoryMap = [];
        $statusRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        if (!empty($data['unsubscribedContactConnectorStatus'])) {
            $status = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Status');

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
            $status = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Status');

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
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);
    }
}
