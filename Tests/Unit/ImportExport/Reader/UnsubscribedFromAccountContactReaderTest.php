<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport\Reader;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\UnsubscribedFromAccountContactReader;
use Oro\Bundle\DotmailerBundle\Provider\Connector\AbstractDotmailerConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Connector\UnsubscribedContactConnector;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Psr\Log\LoggerInterface;

class UnsubscribedFromAccountContactReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var ConnectorContextMediator|\PHPUnit\Framework\MockObject\MockObject */
    private $contextMediator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var UnsubscribedFromAccountContactReader */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->contextMediator = $this->createMock(ConnectorContextMediator::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->reader = new UnsubscribedFromAccountContactReader(
            $this->contextRegistry,
            $this->contextMediator,
            $this->managerRegistry,
            $this->logger
        );
    }

    /**
     * @dataProvider readerDataProvider
     */
    public function testReader(array $data, ?\DateTime $expectedDate)
    {
        $channel = $this->createMock(Channel::class);

        $iterator = $this->createMock(\Iterator::class);

        $transport = $this->createMock(DotmailerTransport::class);
        $transport->expects($this->once())
            ->method('getUnsubscribedFromAccountsContacts')
            ->with($expectedDate)
            ->willReturn($iterator);

        $this->setupReaderDependenciesStubs($data, $channel, $transport);
        $this->reader->read();

        $this->assertEquals($iterator, $this->reader->getSourceIterator());
    }

    public function readerDataProvider(): array
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

    private function setupReaderDependenciesStubs(array $data, Channel $channel, DotmailerTransport $transport): void
    {
        $statusRepositoryMap = [];
        $statusRepository = $this->createMock(EntityRepository::class);
        if (!empty($data['unsubscribedContactConnectorStatus'])) {
            $status = $this->createMock(Status::class);

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
            $status = $this->createMock(Status::class);

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
            ->willReturnMap([
                ['OroIntegrationBundle:Status', null, $statusRepository]
            ]);
        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->willReturn($channel);

        $this->contextMediator->expects($this->any())
            ->method('getInitializedTransport')
            ->with($channel)
            ->willReturn($transport);
        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects($this->any())
            ->method('getStepExecutions')
            ->willReturn(new ArrayCollection([]));
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);
        $context = $this->createMock(ContextInterface::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);
    }
}
