<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\Resources\IResources;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactsIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedFromAccountContactsIterator;

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
     * @return \Iterator
     */
    public function getAddressBooks()
    {
        return new AddressBookIterator($this->dotmailerResources);
    }

    /**
     * @param array     $addressBooks
     * @param \DateTime $lastSyncDate
     * @return UnsubscribedContactsIterator
     */
    public function getUnsubscribedContacts(array $addressBooks, \DateTime $lastSyncDate = null)
    {
        $iterator = new \AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new UnsubscribedContactsIterator($this->dotmailerResources, $addressBook, $lastSyncDate)
            );
        }

        return $iterator;
    }

    /**
     * @param \DateTime $lastSyncDate
     * @return UnsubscribedFromAccountContactsIterator
     */
    public function getUnsubscribedFromAccountsContacts(\DateTime $lastSyncDate = null)
    {
        return new UnsubscribedFromAccountContactsIterator($this->dotmailerResources, $lastSyncDate);
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
     * @param array $aBooksToSynchronize
     * @return \Iterator
     */
    public function getCampaigns(array $aBooksToSynchronize = [])
    {
        if (!$aBooksToSynchronize) {
            return new \EmptyIterator();
        }

        return new CampaignIterator($this->dotmailerResources, $aBooksToSynchronize);
    }
}
