<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
