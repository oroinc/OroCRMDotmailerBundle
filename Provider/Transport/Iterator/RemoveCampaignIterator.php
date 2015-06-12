<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class RemoveCampaignIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array Array of address book origin Ids
     */
    protected $keepCampaigns;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param ManagerRegistry $registry
     * @param Channel         $channel
     * @param array           $keepCampaigns
     */
    public function __construct(ManagerRegistry $registry, Channel $channel, array $keepCampaigns)
    {
        $this->registry = $registry;
        $this->channel = $channel;
        $this->keepCampaigns = $keepCampaigns;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $campaignsForRemoveQB = $this->registry
            ->getRepository('OroCRMDotmailerBundle:Campaign')
            ->getCampaignsForRemoveQB($this->channel, $this->keepCampaigns)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $campaignsForRemoveQB
            ->getQuery()
            ->execute();
    }
}
