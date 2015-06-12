<?php

namespace OroCRM\Bundle\DotmailerBundle\Transport;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\CampaignBundle\Transport\TransportInterface;
use OroCRM\Bundle\CampaignBundle\Transport\VisibilityTransportInterface;
use OroCRM\Bundle\DotmailerBundle\Form\Type\DotmailerTransportSettingsType;

class DotmailerEmailCampaignTransport implements TransportInterface, VisibilityTransportInterface
{
    const NAME = 'dotmailer';

    /**
     * {@inheritdoc}
     */
    public function send(EmailCampaign $campaign, $entity, array $from, array $to)
    {
        //CBORO-10 do not required realization of this method
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.emailcampaign.transport.' . self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return DotmailerTransportSettingsType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransportSettings';
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibleInForm()
    {
        return false;
    }
}
