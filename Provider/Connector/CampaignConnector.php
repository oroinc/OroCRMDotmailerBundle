<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

class CampaignConnector extends AbstractDotmailerConnector implements ParallelizableInterface
{
    const TYPE = 'campaign';
    const JOB_IMPORT = 'dotmailer_campaign_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Campaigns.');
        $entities = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->getConnectedAddressBooks(
                $this->getChannel(),
                $this->getAddressBookId(),
                $throwExceptionOnNotFound = false
            );

        $addressBooks = [];
        foreach ($entities as $entity) {
            $addressBooks[] = [
                'originId' => $entity->getOriginId(),
            ];
        }

        return $this->transport->getCampaigns($addressBooks);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dotmailer.connector.campaign.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
