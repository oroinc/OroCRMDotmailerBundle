<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CampaignBundle\Entity\TransportSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class DotmailerTransportSettings extends TransportSettings
{
    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel")
     * @ORM\JoinColumn(name="dotmailer_channel_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $channel;

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     *
     * @return DotmailerTransportSettings
     */
    public function setChannel(Channel $channel = null)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'channel' => $this->getChannel(),
                ]
            );
        }

        return $this->settings;
    }
}
