<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Processor;

use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Processor\MappedFieldsChangeProcessor;
use Oro\Bundle\DotmailerBundle\QueryDesigner\ParentEntityFindQueryConverter;

class MappedFieldsChangeProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryConverter;

    /** @var MappedFieldsChangeProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->queryConverter = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\QueryDesigner\ParentEntityFindQueryConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new MappedFieldsChangeProcessor(
            $this->doctrineHelper,
            $this->queryConverter
        );
    }

    public function testProcessFieldChangesQueue()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $repository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->at(0))->method('getEntityRepositoryForClass')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($repository));
        $this->doctrineHelper->expects($this->any())->method('getEntityManager')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($em));
        $logs = [];
        $log = new ChangedFieldLog();
        $log->setChannelId(1);
        $log->setParentEntity('ParentEntityClass');
        $log->setRelatedFieldPath('RelatedPath');
        $log->setRelatedId(42);
        $logs[] = $log;
        $repository->expects($this->once())->method('findBy')
            ->with([], null, MappedFieldsChangeProcessor::DEFAULT_BATCH)
            ->will($this->returnValue($logs));
        $abContactRepository = $this
            ->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->at(2))->method('getEntityRepositoryForClass')
            ->with(AddressBookContact::class)
            ->will($this->returnValue($abContactRepository));
        $column = [
            'name' => 'RelatedPath',
            'value' => 42
        ];
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $this->queryConverter->expects($this->once())->method('convert')->with('ParentEntityClass', [$column])
            ->will($this->returnValue($qb));
        $entityId = 64;
        $query->expects($this->once())->method('getOneOrNullResult')->will(
            $this->returnValue([ParentEntityFindQueryConverter::PARENT_ENTITY_ID_ALIAS => $entityId])
        );

        $abContact = new AddressBookContact();
        $abContactRepository->expects($this->once())->method('findOneBy')->with(
            [
                'channel'                => 1,
                'marketingListItemId'    => $entityId,
                'marketingListItemClass' => 'ParentEntityClass',
                'entityUpdated'          => false
            ]
        )->will($this->returnValue($abContact));

        $em->expects($this->once())->method('persist')->with($abContact);
        $em->expects($this->once())->method('remove')->with($log);
        $em->expects($this->once())->method('flush');

        $this->processor->processFieldChangesQueue();

        $this->assertEquals(true, $abContact->isEntityUpdated());
    }

    public function testProcessFieldChangesQueueWithException()
    {
        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $repository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->at(0))->method('getEntityRepositoryForClass')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($repository));
        $this->doctrineHelper->expects($this->any())->method('getEntityManager')
            ->with(ChangedFieldLog::class)
            ->will($this->returnValue($em));
        $logs = [];
        $log = new ChangedFieldLog();
        $log->setChannelId(1);
        $log->setParentEntity('ParentEntityClass');
        $log->setRelatedFieldPath('RelatedPath');
        $log->setRelatedId(42);
        $logs[] = $log;
        $repository->expects($this->once())->method('findBy')
            ->with([], null, MappedFieldsChangeProcessor::DEFAULT_BATCH)
            ->will($this->returnValue($logs));

        $this->queryConverter->expects($this->once())->method('convert')
            ->will($this->throwException(new \Exception('something went wrong')));

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);
        $logger->expects($this->once())->method('warning')->with(
            sprintf(
                'Changes for %s relation field %s with id %s were not processed',
                'ParentEntityClass',
                'RelatedPath',
                42
            ),
            ['message' => 'something went wrong']
        );
        $em->expects($this->never())->method('persist');
        $em->expects($this->once())->method('remove')->with($log);
        $em->expects($this->once())->method('flush');

        $this->processor->processFieldChangesQueue();
    }
}
