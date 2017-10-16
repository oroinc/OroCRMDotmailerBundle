<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

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
        $campaignRepo = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:Campaign');
        $qb = $campaignRepo->createQueryBuilder('campaign')
            ->where('campaign.channel = :channel')
            ->andWhere('campaign.deleted = :deleted')
            ->setParameters(
                [
                    'channel' => $this->getChannel(),
                    'deleted' => false
                ]
            );
        if ($aBookId = $this->getAddressBookId()) {
            $qb->innerJoin('campaign.addressBooks', 'addressBooks')
                ->andWhere('addressBooks.id = :aBookId')
                ->setParameter('aBookId', $aBookId);
        }
        $campaigns = $qb->getQuery()->getResult();

        $activityRepository = $this->managerRegistry->getRepository('OroDotmailerBundle:Activity');
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
        return 'oro.dotmailer.connector.activity_contact.label';
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
