<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use DotMailer\Api\DataTypes\ApiDataField;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class DataFieldManager
{
    /**
     * @var DotmailerTransport
     */
    protected $dotmailerTransport;

    public function __construct(DotmailerTransport $dotmailerTransport)
    {
        $this->dotmailerTransport = $dotmailerTransport;
    }

    /**
     * Create datafield in dotmailer
     */
    public function createOriginDataField(DataField $field)
    {
        $this->initTransport($field);
        $data = $this->prepareDataField($field);
        $this->dotmailerTransport->createDataField($data);
    }

    /**
     * Remove data field from dotmailer
     *
     * @param DataField $field
     * @return array
     */
    public function removeOriginDataField(DataField $field)
    {
        $this->initTransport($field);
        $result = $this->dotmailerTransport->removeDataField($field->getName());

        return $result;
    }

    protected function initTransport(DataField $field)
    {
        $this->dotmailerTransport->init($field->getChannel()->getTransport());
    }

    /**
     * @param DataField $field
     * @return ApiDataField
     * @throws InvalidDefaultValueException
     */
    protected function prepareDataField(DataField $field)
    {
        $defaultValue = $field->getDefaultValue();
        if ($defaultValue) {
            switch ($field->getType()->getId()) {
                case DataField::FIELD_TYPE_NUMERIC:
                    if (!is_numeric($defaultValue)) {
                        throw new InvalidDefaultValueException('Default value must be numeric.');
                    }
                    $defaultValue = (int)$defaultValue;
                    break;
                case DataField::FIELD_TYPE_BOOLEAN:
                    $defaultValue = ($defaultValue === DataField::DEFAULT_BOOLEAN_YES) ? true :
                        (($defaultValue === DataField::DEFAULT_BOOLEAN_NO) ? false : null);
                    break;
                case DataField::FIELD_TYPE_DATE:
                    if (!$defaultValue instanceof \DateTime) {
                        throw new InvalidDefaultValueException('Default value must be valid date.');
                    }
                    //convert to format required by API
                    $defaultValue = $defaultValue->format('Y-m-d\TH:i:s');
                    $field->setDefaultValue($defaultValue);
                    break;
            }
        }

        $result = new ApiDataField(
            [
                'Name' => $field->getName(),
                'Type' => $field->getType()->getId(),
                'Visibility' => $field->getVisibility()->getId(),
                'DefaultValue' => $defaultValue
            ]
        );

        return $result;
    }
}
