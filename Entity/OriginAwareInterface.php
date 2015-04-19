<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface OriginAwareInterface
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
