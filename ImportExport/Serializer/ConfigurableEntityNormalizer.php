<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer as BaseNormalizer;

use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;

class ConfigurableEntityNormalizer extends BaseNormalizer
{
    const XS_BOOLEAN_TRUE = 'true';
    const XS_BOOLEAN_FALSE = 'false';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function setManagerRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = parent::denormalize($data, $class, $format, $context);

        $fields = $this->fieldHelper->getFields($class, true);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $hasConfig = $this->fieldHelper->hasConfig($class, $fieldName);

            // denormalize boolean fields
            if (isset($data[$fieldName]) && $hasConfig && $field['type'] == 'boolean') {
                if ($data[$fieldName] === self::XS_BOOLEAN_FALSE) {
                    $this->fieldHelper->setObjectValue($object, $fieldName, false);
                } elseif ($data[$fieldName] === self::XS_BOOLEAN_TRUE) {
                    $this->fieldHelper->setObjectValue($object, $fieldName, true);
                } else {
                    $this->fieldHelper->setObjectValue($object, $fieldName, (bool)$data[$fieldName]);
                }
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        $channelType = empty($context['channelType']) ? null : $context['channelType'];

        return parent::supportsDenormalization($data, $type, $format, $context) && $channelType == ChannelType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return false;
    }
}
