<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DataFieldManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var DataFieldManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->transport = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport')
            ->disableOriginalConstructor()->getMock();
        $this->manager = new DataFieldManager($this->transport);
    }

    public function testCreateOriginDataFieldNumeric()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_NUMERIC));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('123');

        $this->transport->expects($this->once())->method('createDataField')->with(
            $this->callback(
                function ($apiDataField) {
                    $this->assertAttributeEquals(123, 'value', $apiDataField['DefaultValue']);
                    return true;
                }
            )
        );

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldNumericWithException()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_NUMERIC));
        $field->setDefaultValue('String Value');

        $this->expectException('Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException');
        $this->expectExceptionMessage('Default value must be numeric.');

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldBoolean()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_BOOLEAN));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue(DataFieldStub::DEFAULT_BOOLEAN_YES);

        $this->transport->expects($this->once())->method('createDataField')->with(
            $this->callback(
                function ($apiDataField) {
                    $this->assertAttributeEquals(true, 'value', $apiDataField['DefaultValue']);
                    return true;
                }
            )
        );

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldDate()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $now = new \DateTime();
        $field->setDefaultValue($now);

        $this->transport->expects($this->once())->method('createDataField')->with(
            $this->callback(
                function ($apiDataField) use ($now) {
                    $this->assertAttributeEquals($now->format('Y-m-d\TH:i:s'), 'value', $apiDataField['DefaultValue']);
                    return true;
                }
            )
        );

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldDateWithException()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('2016-12-10');

        $this->expectException('Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException');
        $this->expectExceptionMessage('Default value must be valid date.');

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldString()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $this->transport->expects($this->once())->method('init')->with($transport);

        $field->setName('test field');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_STRING));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('string');

        $this->transport->expects($this->once())->method('createDataField')->with(
            $this->callback(
                function ($apiDataField) {
                    $this->assertAttributeEquals('string', 'value', $apiDataField['DefaultValue']);
                    $this->assertAttributeEquals(DataFieldStub::FIELD_TYPE_STRING, 'value', $apiDataField['Type']);
                    $this->assertAttributeEquals(
                        DataFieldStub::VISIBILITY_PRIVATE,
                        'value',
                        $apiDataField['Visibility']
                    );
                    $this->assertAttributeEquals('test field', 'value', $apiDataField['Name']);
                    return true;
                }
            )
        );

        $this->manager->createOriginDataField($field);
    }

    public function testRemoveOriginDataField()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransport();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test_field');
        $this->transport->expects($this->once())->method('init')->with($transport);
        $this->transport->expects($this->once())->method('removeDataField')->with('test_field');

        $this->manager->removeOriginDataField($field);
    }
}
