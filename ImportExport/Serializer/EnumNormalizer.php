<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Serializer;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Normalizes/denormalizes enum values.
 */
class EnumNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    /**
     * @param EnumOptionInterface $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     */
    #[\Override]
    public function normalize($object, ?string $format = null, array $context = [])
    {
        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'priority' => (int)$object->getPriority(),
            'is_default' => (bool)$object->isDefault(),
        ];
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        $reflection = new \ReflectionClass($type);

        $args = [
            'id' => empty($data['id']) ? null : $data['id'],
            'name' => empty($data['name']) ? '' : $data['name'],
            'priority' => empty($data['priority']) ? 0 : $data['priority'],
            'default' => !empty($data['default']),
        ];

        return $reflection->newInstanceArgs($args);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        $channelType = empty($context['channelType']) ? null : $context['channelType'];

        return is_a($type, EnumOptionInterface::class, true)
            && $channelType === ChannelType::TYPE;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof EnumOptionInterface;
    }
}
