<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use DotMailer\Api\DataTypes\ApiDataField;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport as DotmailerTransportEntity;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DataFieldManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DotmailerTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var DataFieldManager */
    private $manager;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(DotmailerTransport::class);

        $this->manager = new DataFieldManager($this->transport);
    }

    public function testCreateOriginDataFieldNumeric()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_NUMERIC));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('123');

        $this->transport->expects(self::once())
            ->method('createDataField')
            ->with(self::callback(static function (ApiDataField $apiDataField) {
                return self::equalTo('{"Name":"test","Type":"Numeric","Visibility":"Private","DefaultValue":123}')
                    ->evaluate($apiDataField->toJson());
            }));

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldNumericWithException()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_NUMERIC));
        $field->setDefaultValue('String Value');

        $this->expectException(InvalidDefaultValueException::class);
        $this->expectExceptionMessage('Default value must be numeric.');

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldBoolean()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_BOOLEAN));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue(DataFieldStub::DEFAULT_BOOLEAN_YES);

        $this->transport->expects(self::once())
            ->method('createDataField')
            ->with(self::callback(static function (ApiDataField $apiDataField) {
                return self::equalTo('{"Name":"test","Type":"Boolean","Visibility":"Private","DefaultValue":true}')
                    ->evaluate($apiDataField->toJson());
            }));

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldDate()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $now = new \DateTime();
        $field->setDefaultValue($now);

        $this->transport->expects(self::once())
            ->method('createDataField')
            ->with(self::callback(static function (ApiDataField $apiDataField) use ($now) {
                return self::equalTo(\sprintf(
                    '{"Name":"test","Type":"Date","Visibility":"Private","DefaultValue":"%s"}',
                    $now->format('Y-m-d\TH:i:s')
                ))->evaluate($apiDataField->toJson());
            }));

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldDateWithException()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_DATE));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('2016-12-10');

        $this->expectException(InvalidDefaultValueException::class);
        $this->expectExceptionMessage('Default value must be valid date.');

        $this->manager->createOriginDataField($field);
    }

    public function testCreateOriginDataFieldString()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $this->transport->expects(self::once())
            ->method('init')
            ->with($transport);

        $field->setName('test field');
        $field->setType(new EnumValueStub(DataFieldStub::FIELD_TYPE_STRING));
        $field->setVisibility(new EnumValueStub(DataFieldStub::VISIBILITY_PRIVATE));
        $field->setDefaultValue('string');

        $this->transport->expects(self::once())
            ->method('createDataField')
            ->with(self::callback(static function (ApiDataField $apiDataField) {
                return self::equalTo(
                    '{"Name":"test field","Type":"String","Visibility":"Private","DefaultValue":"string"}'
                )->evaluate($apiDataField->toJson());
            }));

        $this->manager->createOriginDataField($field);
    }

    public function testRemoveOriginDataField()
    {
        $field = new DataFieldStub();
        $channel = new Channel();
        $transport = new DotmailerTransportEntity();
        $channel->setTransport($transport);
        $field->setChannel($channel);
        $field->setName('test_field');
        $this->transport->expects(self::once())
            ->method('init')
            ->with($transport);
        $this->transport->expects(self::once())
            ->method('removeDataField')
            ->with('test_field');

        $this->manager->removeOriginDataField($field);
    }
}
