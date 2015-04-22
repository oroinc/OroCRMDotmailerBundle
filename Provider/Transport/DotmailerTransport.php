<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\Resources\IResources;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;

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
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param DotmailerResourcesFactory $dotmailerResourcesFactory
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(DotmailerResourcesFactory $dotmailerResourcesFactory, ManagerRegistry $managerRegistry)
    {
        $this->dotmailerResourcesFactory = $dotmailerResourcesFactory;
        $this->managerRegistry = $managerRegistry;
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
     * @return \Iterator
     */
    public function getAddressBooks()
    {
        return new AddressBookIterator($this->dotmailerResources);
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

    /**
     * @link http://apidocs.mailchimp.com/api/2.0/campaigns/list.php
     * @param Channel $channel
     * @return \Iterator
     */
    public function getCampaigns(Channel $channel)
    {
        // Synchronize only campaigns that are connected to subscriber lists that are used within OroCRM.
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBooksToSync($channel);

        if (!$aBooksToSynchronize) {
            return new \ArrayIterator();
        }

        return new CampaignIterator($this->dotmailerResources, $aBooksToSynchronize);
    }
}
