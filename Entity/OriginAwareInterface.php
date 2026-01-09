<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

/**
 * Extends {@see ChannelAwareInterface} to add origin ID tracking for entities
 * from a specific Dotmailer integration channel.
 */
interface OriginAwareInterface extends ChannelAwareInterface
{
    /**
     * Set origin ID.
     *
     * @param int $originId
     *
     * @return Object
     */
    public function setOriginId($originId);

    /**
     * Get origin ID.
     *
     * @return integer
     */
    public function getOriginId();
}
