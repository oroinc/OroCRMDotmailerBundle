<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Serializer;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts string datetime data to DateTime objects for Dotmailer channel operations
 * Normalization is not supported and will throw a RuntimeException
 */
class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
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
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = array()
    ): float|int|bool|\ArrayObject|array|string|null {
        throw new RuntimeException('Do not support normalization.');
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_string($data) && $type === 'DateTime' && !empty($context['channelType'])
            && $context['channelType'] === ChannelType::TYPE;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [\DateTime::class => true];
    }
}
