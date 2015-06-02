<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\ImportExport;

use OroCRM\Bundle\DotmailerBundle\ImportExport\JobContextComposite;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class JobContextCompositeTest extends \PHPUnit_Framework_TestCase
{
    public function testAddError()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('addError');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('addError')
            ->with($expectedError = 'test error');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addError($expectedError);
    }

    public function testAddErrors()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('addErrors');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('addErrors')
            ->with($expectedErrors = ['test error']);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->addErrors($expectedErrors);
    }

    public function testGetFailureExceptions()
    {
        $expected = [
            ['message' => 'second exception'],
            ['message' => 'first exception'],
        ];
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getFailureExceptions')
            ->will($this->returnValue([['message' => 'first exception']]));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getFailureExceptions')
            ->will($this->returnValue([['message' => 'second exception']]));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getFailureExceptions();
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReadCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementReadCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementReadCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReadCount();
    }

    public function testGetReadCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getReadCount')
            ->will($this->returnValue($previousStepContextReadCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getReadCount')
            ->will($this->returnValue($currentStepContextReadCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReadCount();
        $expected = $previousStepContextReadCount + $currentStepContextReadCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReadOffset()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementReadOffset');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementReadOffset');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReadOffset();
    }

    public function testReadOffset()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getReadOffset')
            ->will($this->returnValue($previousStepContextReadOffset = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getReadOffset')
            ->will($this->returnValue($currentStepContextReadOffset = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReadOffset();
        $expected = $previousStepContextReadOffset + $currentStepContextReadOffset;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementAddCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementAddCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementAddCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementAddCount();
    }

    public function testGetAddCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getAddCount')
            ->will($this->returnValue($previousStepContextAddCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getAddCount')
            ->will($this->returnValue($currentStepContextAddCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getAddCount();
        $expected = $previousStepContextAddCount + $currentStepContextAddCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementUpdateCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementAddCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementAddCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementAddCount();
    }

    public function testGetUpdateCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getUpdateCount')
            ->will($this->returnValue($previousStepContextUpdateCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getUpdateCount')
            ->will($this->returnValue($currentStepContextUpdateCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getUpdateCount();
        $expected = $previousStepContextUpdateCount + $currentStepContextUpdateCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementReplaceCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementReplaceCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementReplaceCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementReplaceCount();
    }

    public function testGetReplaceCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getReplaceCount')
            ->will($this->returnValue($previousStepContextReplaceCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getReplaceCount')
            ->will($this->returnValue($currentStepContextReplaceCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getReplaceCount();
        $expected = $previousStepContextReplaceCount + $currentStepContextReplaceCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementDeleteCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementDeleteCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementDeleteCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementDeleteCount();
    }

    public function testGetDeleteCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getDeleteCount')
            ->will($this->returnValue($previousStepContextDeleteCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getDeleteCount')
            ->will($this->returnValue($currentStepContextDeleteCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getDeleteCount();
        $expected = $previousStepContextDeleteCount + $currentStepContextDeleteCount;
        $this->assertEquals($expected, $actual);
    }

    public function testIncrementErrorEntriesCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('incrementErrorEntriesCount');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->incrementErrorEntriesCount();
    }

    public function testGetErrorEntriesCount()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getErrorEntriesCount')
            ->will($this->returnValue($previousStepContextErrorEntriesCount = 21));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getErrorEntriesCount')
            ->will($this->returnValue($currentStepContextErrorEntriesCount = 45));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getErrorEntriesCount();
        $expected = $previousStepContextErrorEntriesCount + $currentStepContextErrorEntriesCount;
        $this->assertEquals($expected, $actual);
    }

    public function testSetValue()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('setValue')
            ->with($expectedValueName = 'testValue', null);
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->at(0))
            ->method('setValue')
            ->with($expectedValueName, null);
        $currentContext->expects($this->at(1))
            ->method('setValue')
            ->with($expectedValueName, $expectedValue = 23456);

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $target->setValue($expectedValueName, $expectedValue);
    }

    public function testGetValueReturnFirstFoundedScalarValue()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getValue')
            ->with($expectedValueName = 'testValue')
            ->will($this->returnValue($expectedValue = 23456));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(null));

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

        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getValue')
            ->with($expectedValueName = 'testValue')
            ->will($this->returnValue([$expectedValue[1]]));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue([$expectedValue[0]]));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getValue($expectedValueName);
        $this->assertEquals($expectedValue, $actual);
    }

    public function testGetConfiguration()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->never())
            ->method('getConfiguration');
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($expectedConfiguration = 'test configuration'));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getConfiguration();
        $this->assertEquals($expectedConfiguration, $actual);
    }

    public function testHasOption()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('hasOption')
            ->with($option = 'testOption')
            ->will($this->returnValue(true));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('hasOption')
            ->with($option = 'testOption')
            ->will($this->returnValue(false));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->hasOption($option);
        $this->assertTrue($actual);
    }

    public function testGetOptionReturnFirstFoundedScalarValue()
    {
        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getOption')
            ->with($expectedOptionName = 'testOption')
            ->will($this->returnValue($expectedValue = 23456));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

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

        $previousStepContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $previousStepContext->expects($this->once())
            ->method('getOption')
            ->with($expectedOptionName = 'testOption')
            ->will($this->returnValue([$expectedValue[1]]));
        $contexts = [$previousStepContext];
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue([$expectedValue[0]]));

        $target = $this->initCompositeStubs($currentContext, $contexts);

        $actual = $target->getOption($expectedOptionName);
        $this->assertEquals($expectedValue, $actual);
    }


    public function testGetOptionReturnCorrectDefaultValue()
    {
        $expectedOptionName = 'notExistOption';
        $currentContext = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $currentContext->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $target = $this->initCompositeStubs($currentContext, []);

        $actual = $target->getOption($expectedOptionName, $expectedValue = ['testDefault']);
        $this->assertEquals($expectedValue, $actual);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject   $currentContext
     * @param \PHPUnit_Framework_MockObject_MockObject[] $contexts
     *
     * @return JobContextComposite
     */
    protected function initCompositeStubs(\PHPUnit_Framework_MockObject_MockObject $currentContext, array $contexts)
    {
        $currentStepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $stepExecutions = [$currentStepExecution];
        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $currentStepExecution->expects($this->any())
            ->method('getJobExecution')
            ->will($this->returnValue($jobExecution));

        $map = [
            [$currentStepExecution, $currentContext]
        ];
        foreach ($contexts as $context) {
            $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
                ->disableOriginalConstructor()
                ->getMock();
            $map[] = [$stepExecution, $context];
            $stepExecutions[] = $stepExecution;
        }
        $jobExecution->expects($this->any())
            ->method('getStepExecutions')
            ->will($this->returnValue($stepExecutions));

        $registry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getByStepExecution')
            ->will(
                $this->returnValueMap(
                    $map
                )
            );
        $target = new JobContextComposite($currentStepExecution, $registry);

        return $target;
    }
}
