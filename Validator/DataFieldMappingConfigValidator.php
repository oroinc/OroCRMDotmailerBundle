<?php

namespace Oro\Bundle\DotmailerBundle\Validator;

use Doctrine\DBAL\Types\Type;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Validator\Constraints\DataFieldMappingConfigConstraint;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;

/**
 * Validate compatibility of entity field and dotmailer data field types
 */
class DataFieldMappingConfigValidator extends ConstraintValidator
{
    const ALIAS = 'oro_dotmailer.validator.datafield_mapping_config';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
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
            return $this->translator->trans('oro.dotmailer.datafield_mapping_config.validation.multiple');
        }

        $joinIdentifierHelper = new JoinIdentifierHelper($entity->getMapping()->getEntity());
        $field = $entity->getEntityFields();
        $class = $joinIdentifierHelper->getEntityClassName($field);
        $fieldName = $joinIdentifierHelper->getFieldName($field);
        $fieldType = $this->doctrineHelper->getEntityMetadata($class)->getTypeOfField($fieldName);
        $isCompatible = false;
        switch ($type) {
            case DataField::FIELD_TYPE_NUMERIC:
                $numericeTypes = [Type::BIGINT, Type::SMALLINT, Type::INTEGER, Type::DECIMAL, Type::FLOAT];
                $isCompatible = in_array($fieldType, $numericeTypes);
                break;
            case DataField::FIELD_TYPE_DATE:
                $dateTypes = [Type::DATETIME, Type::DATETIMETZ, Type::DATE];
                $isCompatible = in_array($fieldType, $dateTypes);
                break;
            case DataField::FIELD_TYPE_BOOLEAN:
                $bolleanTypes = [Type::BOOLEAN];
                $isCompatible = in_array($fieldType, $bolleanTypes);
                break;
            default:
                $complexDataTypes = [Type::BINARY, Type::BLOB, Type::OBJECT];
                $isCompatible = !in_array($fieldType, $complexDataTypes);
        }
        $message = '';
        if (!$isCompatible) {
            $dataFieldName = $entity->getDataField()->getName();
            $message = $this->translator->trans(
                'oro.dotmailer.datafield_mapping_config.validation.incompatible_types_' . strtolower($type),
                ['%datafield%' => $dataFieldName]
            );
        }

        return $message;
    }
}
