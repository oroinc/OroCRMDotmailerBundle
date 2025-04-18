<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Iterator for campaigns remove.
 */
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

    public function __construct(ManagerRegistry $registry, Channel $channel, array $keepCampaigns)
    {
        $this->registry = $registry;
        $this->channel = $channel;
        $this->keepCampaigns = $keepCampaigns;
    }

    #[\Override]
    protected function getItems($take, $skip)
    {
        $campaignsForRemoveQB = $this->registry
            ->getRepository(Campaign::class)
            ->getCampaignsForRemoveQB($this->channel, $this->keepCampaigns)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $campaignsForRemoveQB
            ->getQuery()
            ->execute();
    }
}
