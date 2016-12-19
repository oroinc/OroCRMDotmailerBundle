<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Validator;

use Doctrine\DBAL\Types\Type;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use OroCRM\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use OroCRM\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use OroCRM\Bundle\DotmailerBundle\Validator\DataFieldMappingConfigValidator;
use OroCRM\Bundle\DotmailerBundle\Validator\Constraints\DataFieldMappingConfigConstraint;

class DataFieldMappingConfigValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

     /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var DataFieldMappingConfigValidator */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->validator = new DataFieldMappingConfigValidator($this->doctrineHelper, $this->translator);
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
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadata')->with('entityClass')
            ->will($this->returnValue($classMetadata));
        $classMetadata->expects($this->once())->method('getTypeOfField')->with('entityFieldName')
            ->will($this->returnValue(Type::STRING));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'orocrm.dotmailer.datafieldmappingconfig.validation.incompatible_types_numeric',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
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
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadata')->with('entityClass')
            ->will($this->returnValue($classMetadata));
        $classMetadata->expects($this->once())->method('getTypeOfField')->with('entityFieldName')
            ->will($this->returnValue(Type::STRING));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'orocrm.dotmailer.datafieldmappingconfig.validation.incompatible_types_boolean',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
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
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadata')->with('entityClass')
            ->will($this->returnValue($classMetadata));
        $classMetadata->expects($this->once())->method('getTypeOfField')->with('entityFieldName')
            ->will($this->returnValue(Type::STRING));

        $this->translator->expects($this->once())->method('trans')
            ->with(
                'orocrm.dotmailer.datafieldmappingconfig.validation.incompatible_types_date',
                ['%datafield%' => 'dataFieldName']
            )
            ->will($this->returnValue('translated error message'));
        $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
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
            ->with('orocrm.dotmailer.datafieldmappingconfig.validation.multiple')
            ->will($this->returnValue('translated error message'));
        $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $this->context->expects($this->once())->method('buildViolation')->with('translated error message', [])
            ->will($this->returnValue($violation));

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }

    public function testValidatePassed()
    {
        $mappingConfig = new DataFieldMappingConfig();
        $dataField = new DataFieldStub();
        $dataField->setType(new EnumValueStub(DataField::FIELD_TYPE_STRING));
        $dataField->setName('dataFieldName');
        $mappingConfig->setDataField($dataField);
        $mappingConfig->setEntityFields('entityFieldName');
        $mapping = new DataFieldMapping();
        $mapping->setEntity('entityClass');
        $mappingConfig->setMapping($mapping);
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->doctrineHelper->expects($this->once())->method('getEntityMetadata')->with('entityClass')
            ->will($this->returnValue($classMetadata));
        $classMetadata->expects($this->once())->method('getTypeOfField')->with('entityFieldName')
            ->will($this->returnValue(Type::STRING));

        $this->translator->expects($this->never())->method('trans');
        $this->context->expects($this->never())->method('buildViolation');

        $constraint = new DataFieldMappingConfigConstraint();
        $this->validator->validate($mappingConfig, $constraint);
    }
}
