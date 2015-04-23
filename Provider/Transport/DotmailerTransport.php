<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;

use DotMailer\Api\Resources\IResources;

class DotmailerTransport implements TransportInterface
{
    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var DotmailerResourcesFactory
     */
    protected $dotMailerResFactory;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param DotmailerResourcesFactory $dotMailerResFactory
     * @param ManagerRegistry           $managerRegistry
     */
    public function __construct(DotmailerResourcesFactory $dotMailerResFactory, ManagerRegistry $managerRegistry)
    {
        $this->dotMailerResFactory = $dotMailerResFactory;
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

        $this->dotmailerResources = $this->dotMailerResFactory->createResources($username, $password);
    }

    /**
     * @param Channel $channel
     *
     * @return ContactIterator
     */
    public function getContacts(Channel $channel)
    {
        $aBooksToSynchronize = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->getAddressBookIdsToSync($channel);

        if (!$aBooksToSynchronize) {
            return new \ArrayIterator();
        }

        return new ContactIterator($this->dotmailerResources, $aBooksToSynchronize);
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
