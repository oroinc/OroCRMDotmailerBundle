<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Serializer;

use Symfony\Component\Serializer\Exception\RuntimeException;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer as BaseNormalizer;

use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;

class DateTimeNormalizer extends BaseNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (empty($data)) {
            return null;
        }

        $datetime = new \DateTime($data);

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s".', $data));
        }

        return $datetime;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return parent::normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return parent::supportsDenormalization($data, $type, $format, $context)
            && $context['channelType'] == ChannelType::TYPE;
    }
}
