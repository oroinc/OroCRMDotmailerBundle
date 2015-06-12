<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class ActivityContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'activity_contact';
    const JOB_IMPORT = 'dotmailer_activity_contact_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        // Synchronize only campaign activities that are connected to marketing list.
        $campaigns = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Campaign')
            ->findBy(['channel' => $this->getChannel(), 'deleted' => false]);

        $activityRepository = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:Activity');
        $campaignsToSynchronize = [];

        foreach ($campaigns as $campaign) {
            $campaignsToSynchronize[] = [
                'originId' => $campaign->getOriginId(),
                'isInit'   => $activityRepository->isExistsActivityByCampaign($campaign),
            ];
        }

        return $this->transport->getActivityContacts($campaignsToSynchronize, $this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.activity_contact.label';
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
