<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\DotmailerBundle\ImportExport\JobContextComposite;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobContextCompositeTest extends \PHPUnit\Framework\TestCase
{
    public function testAddError()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('addError');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('addError')
            ->with($expectedError = 'test error');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addError($expectedError);
    }

    public function testAddErrors()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('addErrors');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('addErrors')
            ->with($expectedErrors = ['test error']);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addErrors($expectedErrors);
    }

    public function testAddPostponedRow()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('addPostponedRow');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('addPostponedRow')
            ->with($expectedPostponedRow = ['header_1' => 1]);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addPostponedRow($expectedPostponedRow);
    }

    public function testAddPostponedRows()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('addPostponedRows');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('addPostponedRows')
            ->with($expectedPostponedRows = [['header_1' => 1], ['header_1' => 2]]);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addPostponedRows($expectedPostponedRows);
    }

    public function testGetFailureExceptions()
    {
        $expected = [
            ['message' => 'second exception'],
            ['message' => 'first exception'],
        ];
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getFailureExceptions')
            ->willReturn([['message' => 'first exception']]);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getFailureExceptions')
            ->willReturn([['message' => 'second exception']]);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getFailureExceptions();
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReadCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementReadCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementReadCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReadCount();
    }

    public function testGetReadCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getReadCount')
            ->willReturn($previousStepContextReadCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getReadCount')
            ->willReturn($currentStepContextReadCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReadCount();
        $expected = $previousStepContextReadCount + $currentStepContextReadCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReadOffset()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementReadOffset');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementReadOffset');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReadOffset();
    }

    public function testReadOffset()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getReadOffset')
            ->willReturn($previousStepContextReadOffset = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getReadOffset')
            ->willReturn($currentStepContextReadOffset = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReadOffset();
        $expected = $previousStepContextReadOffset + $currentStepContextReadOffset;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementAddCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementAddCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementAddCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementAddCount();
    }

    public function testGetAddCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getAddCount')
            ->willReturn($previousStepContextAddCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getAddCount')
            ->willReturn($currentStepContextAddCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getAddCount();
        $expected = $previousStepContextAddCount + $currentStepContextAddCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementUpdateCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementAddCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementAddCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementAddCount();
    }

    public function testGetUpdateCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getUpdateCount')
            ->willReturn($previousStepContextUpdateCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getUpdateCount')
            ->willReturn($currentStepContextUpdateCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getUpdateCount();
        $expected = $previousStepContextUpdateCount + $currentStepContextUpdateCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReplaceCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementReplaceCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementReplaceCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReplaceCount();
    }

    public function testGetReplaceCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getReplaceCount')
            ->willReturn($previousStepContextReplaceCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getReplaceCount')
            ->willReturn($currentStepContextReplaceCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReplaceCount();
        $expected = $previousStepContextReplaceCount + $currentStepContextReplaceCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementDeleteCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementDeleteCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementDeleteCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementDeleteCount();
    }

    public function testGetDeleteCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getDeleteCount')
            ->willReturn($previousStepContextDeleteCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getDeleteCount')
            ->willReturn($currentStepContextDeleteCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getDeleteCount();
        $expected = $previousStepContextDeleteCount + $currentStepContextDeleteCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementErrorEntriesCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementErrorEntriesCount();
    }

    public function testGetErrorEntriesCount()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getErrorEntriesCount')
            ->willReturn($previousStepContextErrorEntriesCount = 21);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getErrorEntriesCount')
            ->willReturn($currentStepContextErrorEntriesCount = 45);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getErrorEntriesCount();
        $expected = $previousStepContextErrorEntriesCount + $currentStepContextErrorEntriesCount;
        $this->assertEquals($expected, $actual);
    }

    public function testSetValue()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('setValue')
            ->with($expectedValueName = 'testValue', null);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $expectedValue = 23456;
        $currentContext->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(
                [$expectedValueName, null],
                [$expectedValueName, $expectedValue]
            );

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->setValue($expectedValueName, $expectedValue);
    }

    public function testGetValueReturnFirstFoundedScalarValue()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getValue')
            ->with($expectedValueName = 'testValue')
            ->willReturn($expectedValue = 23456);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getValue($expectedValueName);
        $this->assertEquals($expectedValue, $actual);
    }

    public function testGetValueMergeArrayValues()
    {
        $expectedValue = [
            ['test value'],
            ['second test value'],
        ];

        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getValue')
            ->with($expectedValueName = 'testValue')
            ->willReturn([$expectedValue[1]]);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getValue')
            ->willReturn([$expectedValue[0]]);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getValue($expectedValueName);
        $this->assertEquals($expectedValue, $actual);
    }

    public function testGetConfiguration()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->never())
            ->method('getConfiguration');
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($expectedConfiguration = 'test configuration');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getConfiguration();
        $this->assertEquals($expectedConfiguration, $actual);
    }

    public function testHasOption()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('hasOption')
            ->with('testOption')
            ->willReturn(true);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('hasOption')
            ->with($option = 'testOption')
            ->willReturn(false);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->hasOption($option);
        $this->assertTrue($actual);
    }

    public function testGetOptionReturnFirstFoundedScalarValue()
    {
        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getOption')
            ->with($expectedOptionName = 'testOption')
            ->willReturn($expectedValue = 23456);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getOption')
            ->willReturn(null);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getOption($expectedOptionName);
        $this->assertEquals($expectedValue, $actual);
    }

    public function testGetOptionMergeArrayValues()
    {
        $expectedValue = [
            ['test value'],
            ['second test value'],
        ];

        $previousStepContext = $this->createMock(ContextInterface::class);
        $previousStepContext->expects($this->once())
            ->method('getOption')
            ->with($expectedOptionName = 'testOption')
            ->willReturn([$expectedValue[1]]);
        $contexts = [$previousStepContext];
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getOption')
            ->willReturn([$expectedValue[0]]);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getOption($expectedOptionName);
        $this->assertEquals($expectedValue, $actual);
    }

    public function testGetOptionReturnCorrectDefaultValue()
    {
        $expectedOptionName = 'notExistOption';
        $currentContext = $this->createMock(ContextInterface::class);
        $currentContext->expects($this->once())
            ->method('getOption')
            ->willReturn(null);

        $target = $this->initCompositeStubs($currentContext, []);

        $actual = $target->getOption($expectedOptionName, $expectedValue = ['testDefault']);
        $this->assertEquals($expectedValue, $actual);
    }

    private function initCompositeStubs(
        ContextInterface|\PHPUnit\Framework\MockObject\MockObject $currentContext,
        array $contexts
    ): JobContextComposite {
        $currentStepExecution = $this->createMock(StepExecution::class);

        $stepExecutions = [$currentStepExecution];
        $jobExecution = $this->createMock(JobExecution::class);
        $currentStepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);

        $map = [
            [$currentStepExecution, $currentContext]
        ];
        /** @var ContextInterface $context */
        foreach ($contexts as $context) {
            $stepExecution = $this->createMock(StepExecution::class);
            $map[] = [$stepExecution, $context];
            $stepExecutions[] = $stepExecution;
        }
        $jobExecution->expects($this->any())
            ->method('getStepExecutions')
            ->willReturn(new ArrayCollection($stepExecutions));

        $registry = $this->createMock(ContextRegistry::class);
        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturnMap($map);

        return new JobContextComposite($currentStepExecution, $registry);
    }
}
