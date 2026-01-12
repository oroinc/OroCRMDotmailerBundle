<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Interface for entities that are aware of their associated Dotmailer channel.
 */
interface ChannelAwareInterface
{
    /**
     * Set integration channel.
     *
     * @param Channel $channel
     *
     * @return mixed
     */
    public function setChannel(Channel $channel);

    /**
     * Get integration channel.
     *
     * @return Channel
     */
    public function getChannel();
}
