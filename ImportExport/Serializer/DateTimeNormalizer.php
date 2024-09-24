<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Serializer;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class DateTimeNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
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

    #[\Override]
    public function normalize($object, string $format = null, array $context = array())
    {
        throw new RuntimeException('Do not support normalization.');
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_string($data) && $type === 'DateTime' && !empty($context['channelType'])
            && $context['channelType'] === ChannelType::TYPE;
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return false;
    }
}
