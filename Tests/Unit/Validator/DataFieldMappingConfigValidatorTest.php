<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Validator;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use Oro\Bundle\DotmailerBundle\Validator\Constraints\DataFieldMappingConfigConstraint;
use Oro\Bundle\DotmailerBundle\Validator\DataFieldMappingConfigValidator;
use Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types\DurationType;
use Oro\DBAL\Types\MoneyType;
use Oro\DBAL\Types\PercentType;

class DataFieldMappingConfigValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityFieldProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DataFieldMappingConfigValidator */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    protected function setUp(): void
    {
        $this->entityFieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Contracts\Translation\TranslatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->validator = new DataFieldMappingConfigValidator($this->entityFieldProvider, $this->translator);
        $this->context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->getMock();
        $this->validator->initialize($this->context);
    }

    public function testValidateNumericFailed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_NUMERIC));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName');
        $mapping = new DataFieldMapping();
        $mapping->setEntity('entityClass');
        $mappingConfig->setMapping($mapping);
        $this->entityFieldProvider->expects($this->once())->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->will($this->returnValue([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_numeric',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $this->context->expects($this->once())->method('buildViolation')->with('translated error message', [])
            ->will($this->returnValue($violation));

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    public function testValidateBooleanFailed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_BOOLEAN));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName');
        $mapping = new DataFieldMapping();
        $mapping->setEntity('entityClass');
        $mappingConfig->setMapping($mapping);
        $this->entityFieldProvider->expects($this->once())->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->will($this->returnValue([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_boolean',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $this->context->expects($this->once())->method('buildViolation')->with('translated error message', [])
            ->will($this->returnValue($violation));

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    public function testValidateDateFailed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_DATE));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName');
        $mapping = new DataFieldMapping();
        $mapping->setEntity('entityClass');
        $mappingConfig->setMapping($mapping);
        $this->entityFieldProvider->expects($this->once())->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->will($this->returnValue([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_date',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $this->context->expects($this->once())->method('buildViolation')->with('translated error message', [])
            ->will($this->returnValue($violation));

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    public function testValidateMutlipleFieldsFailed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_BOOLEAN));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName,anotherEntityFieldName');

        $this->translator->expects($this->once())->method('trans')
            ->with('oro.dotmailer.datafieldmappingconfig.validation.multiple')
            ->will($this->returnValue('translated error message'));
        $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $this->context->expects($this->once())->method('buildViolation')->with('translated error message', [])
            ->will($this->returnValue($violation));

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    /**
     * @dataProvider dataFieldDataProvider
     */
    public function testValidatePassed($dataFieldType, $type)
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub($dataFieldType));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName');
        $mapping = new DataFieldMapping();
        $mapping->setEntity('entityClass');
        $mappingConfig->setMapping($mapping);
        $this->entityFieldProvider->expects($this->once())->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->will($this->returnValue([
                [
                    'name' => 'entityFieldName',
                    'type' => $type
                ]
            ]));

        $this->translator->expects($this->never())->method('trans');
        $this->context->expects($this->never())->method('buildViolation');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    /**
     * @return array
     */
    public function dataFieldDataProvider()
    {
        return [
            'string'    => [DataField::FIELD_TYPE_STRING, Types::STRING],
            'integer'   => [DataField::FIELD_TYPE_NUMERIC, Types::INTEGER],
            'binint'    => [DataField::FIELD_TYPE_NUMERIC, Types::BIGINT],
            'smallint'  => [DataField::FIELD_TYPE_NUMERIC, Types::SMALLINT],
            'decimal'   => [DataField::FIELD_TYPE_NUMERIC, Types::DECIMAL],
            'float'     => [DataField::FIELD_TYPE_NUMERIC, Types::FLOAT],
            'money'     => [DataField::FIELD_TYPE_NUMERIC, MoneyType::TYPE],
            'percent'   => [DataField::FIELD_TYPE_NUMERIC, PercentType::TYPE],
            'duration'  => [DataField::FIELD_TYPE_NUMERIC, DurationType::TYPE],
            'datetime'  => [DataField::FIELD_TYPE_DATE, Types::DATETIME_MUTABLE],
            'datetimez' => [DataField::FIELD_TYPE_DATE, Types::DATETIMETZ_MUTABLE],
            'date'      => [DataField::FIELD_TYPE_DATE, Types::DATE_MUTABLE],
            'boolean'   => [DataField::FIELD_TYPE_BOOLEAN, Types::BOOLEAN],
        ];
    }
}
