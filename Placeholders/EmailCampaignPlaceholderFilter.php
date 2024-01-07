<?php

namespace Oro\Bundle\DotmailerBundle\Placeholders;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Transport\DotmailerEmailCampaignTransport;

/**
 * Filters placeholders for email campaigns.
 */
class EmailCampaignPlaceholderFilter
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks the object is an instance of a given class.
     *
     * @param $entity
     * @return bool
     */
    public function isApplicableOnEmailCampaign($entity)
    {
        if ($entity instanceof EmailCampaign && $entity->getTransport() == DotmailerEmailCampaignTransport::NAME) {
            $campaign = $this->registry
                ->getRepository(Campaign::class)
                ->findOneBy(['emailCampaign' => $entity]);

            return (bool) $campaign;
        }

        return false;
    }
}
