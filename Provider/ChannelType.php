<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Defines the channel type and configuration for Dotmailer integration.
 */
class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'dotmailer';

    #[\Override]
    public function getLabel()
    {
        return 'oro.dotmailer.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/orodotmailer/img/dotmailer.ico';
    }
}
