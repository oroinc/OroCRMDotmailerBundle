<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Serializer;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;

class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (empty($data)) {
            return null;
        }

        try {
            $datetime = new \DateTime($data);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Invalid datetime "%s".', $data));
        }

        return $datetime;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        throw new RuntimeException('Do not support normalization.');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_string($data) && $type === 'DateTime' && !empty($context['channelType'])
            && $context['channelType'] == ChannelType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return false;
    }
}
