<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'dotmailer';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dotmailer.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/orodotmailer/img/dotmailer.ico';
    }
}
