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
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\DBAL\Types\MoneyType;
use Oro\DBAL\Types\PercentType;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataFieldMappingConfigValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityFieldProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->entityFieldProvider = $this->createMock(EntityFieldProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new DataFieldMappingConfigValidator($this->entityFieldProvider, $this->translator);
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
        $this->entityFieldProvider->expects($this->once())
            ->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->willReturn([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_numeric',
                ['%datafield%' => 'dataFieldName']
            )
            ->willReturn('translated error message');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);

        $this->buildViolation('translated error message')
            ->assertRaised();
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
        $this->entityFieldProvider->expects($this->once())
            ->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->willReturn([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_boolean',
                ['%datafield%' => 'dataFieldName']
            )
            ->willReturn('translated error message');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);

        $this->buildViolation('translated error message')
            ->assertRaised();
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
        $this->entityFieldProvider->expects($this->once())
            ->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->willReturn([
                [
                    'name' => 'entityFieldName',
                    'type' => Types::STRING
                ]
            ]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_date',
                ['%datafield%' => 'dataFieldName']
            )
            ->willReturn('translated error message');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);

        $this->buildViolation('translated error message')
            ->assertRaised();
    }

    public function testValidateMutlipleFieldsFailed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_BOOLEAN));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName,anotherEntityFieldName');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.dotmailer.datafieldmappingconfig.validation.multiple')
            ->willReturn('translated error message');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);

        $this->buildViolation('translated error message')
            ->assertRaised();
    }

    /**
     * @dataProvider dataFieldDataProvider
     */
    public function testValidatePassed(string $dataFieldType, string $type)
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
        $this->entityFieldProvider->expects($this->once())
            ->method('getFields')
            ->with('entityClass', false, true, false, false, false, false)
            ->willReturn([
                [
                    'name' => 'entityFieldName',
                    'type' => $type
                ]
            ]);

        $this->translator->expects($this->never())
            ->method('trans');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);

        $this->assertNoViolation();
    }

    public function dataFieldDataProvider(): array
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
