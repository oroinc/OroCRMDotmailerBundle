<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\Resources\IResources;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;

class DotmailerTransport implements TransportInterface
{
    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var DotmailerResourcesFactory
     */
    protected $dotmailerResourcesFactory;

    /**
     * @param DotmailerResourcesFactory $dotmailerResourcesFactory
     */
    public function __construct(DotmailerResourcesFactory $dotmailerResourcesFactory)
    {
        $this->dotmailerResourcesFactory = $dotmailerResourcesFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $settings = $transportEntity->getSettingsBag();
        $username = $settings->get('username');
        if (!$username) {
            throw new RequiredOptionException('username');
        }
        $password = $settings->get('password');
        if (!$password) {
            throw new RequiredOptionException('password');
        }

        $this->dotmailerResources = $this->dotmailerResourcesFactory->createResources($username, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.integration_transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_dotmailer_transport_setting_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\DotmailerBundle\\Entity\\DotmailerTransport';
    }
}
