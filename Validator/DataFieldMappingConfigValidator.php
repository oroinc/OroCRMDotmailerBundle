<?php

namespace Oro\Bundle\DotmailerBundle\Validator;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Validator\Constraints\DataFieldMappingConfigConstraint;
use Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types\DurationType;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\DBAL\Types\MoneyType;
use Oro\DBAL\Types\PercentType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validate compatibility of entity field and dotmailer data field types
 */
class DataFieldMappingConfigValidator extends ConstraintValidator
{
    const ALIAS = 'oro_dotmailer.validator.datafield_mapping_config';

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $fieldTypes = [];

    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        TranslatorInterface $translator
    ) {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->translator = $translator;
    }

    /**
     * @param DataFieldMappingConfig             $entity
     * @param DataFieldMappingConfigConstraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof DataFieldMappingConfig) {
            return;
        }
        $message = $this->isFieldsTypeCompatible($entity);
        if ($message !== '') {
            /** @var ExecutionContextInterface $context */
            $context = $this->context;
            $context->buildViolation($message, [])
                ->addViolation();
        }
    }

    /**
     * Check that entity field type is compatible with DM data field type
     *
     * @param DataFieldMappingConfig $entity
     *
     * @return string
     */
    protected function isFieldsTypeCompatible(DataFieldMappingConfig $entity)
    {
        $fields = explode(',', $entity->getEntityFields());
        $type = $entity->getDataField()->getType()->getId();
        if (count($fields) > 1 && $type !== DataField::FIELD_TYPE_STRING) {
            //multiple fields can be mapped to the string data field only
            return $this->translator->trans('oro.dotmailer.datafieldmappingconfig.validation.multiple');
        }

        $joinIdentifierHelper = new JoinIdentifierHelper($entity->getMapping()->getEntity());
        $field = $entity->getEntityFields();
        $class = $joinIdentifierHelper->getEntityClassName($field);
        $fieldName = $joinIdentifierHelper->getFieldName($field);
        $fieldTypes = $this->getFieldTypes($class);
        $fieldType = isset($fieldTypes[$fieldName]) ? $fieldTypes[$fieldName] : '';
        switch ($type) {
            case DataField::FIELD_TYPE_NUMERIC:
                $numericTypes = [
                    Types::BIGINT,
                    Types::SMALLINT,
                    Types::INTEGER,
                    Types::DECIMAL,
                    Types::FLOAT,
                    MoneyType::TYPE,
                    PercentType::TYPE,
                    DurationType::TYPE,
                ];
                $isCompatible = in_array($fieldType, $numericTypes);
                break;
            case DataField::FIELD_TYPE_DATE:
                $dateTypes = [Types::DATETIME_MUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATE_MUTABLE];
                $isCompatible = in_array($fieldType, $dateTypes);
                break;
            case DataField::FIELD_TYPE_BOOLEAN:
                $booleanTypes = [Types::BOOLEAN];
                $isCompatible = in_array($fieldType, $booleanTypes);
                break;
            default:
                $complexDataTypes = [Types::BINARY, Types::BLOB, Types::OBJECT];
                $isCompatible = !in_array($fieldType, $complexDataTypes);
        }
        $message = '';
        if (!$isCompatible) {
            $dataFieldName = $entity->getDataField()->getName();
            $message = $this->translator->trans(
                'oro.dotmailer.datafieldmappingconfig.validation.incompatible_types_' . strtolower($type),
                ['%datafield%' => $dataFieldName]
            );
        }

        return $message;
    }

    /**
     * @param string $class
     * @return array
     */
    protected function getFieldTypes($class)
    {
        if (!isset($this->fieldTypes[$class])) {
            $fields = $this->entityFieldProvider->getEntityFields(
                $class,
                EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
            );
            $this->fieldTypes[$class] = array_column($fields, 'type', 'name');
        }

        return $this->fieldTypes[$class];
    }
}
