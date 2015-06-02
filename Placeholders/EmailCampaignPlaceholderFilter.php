<?php

namespace OroCRM\Bundle\DotmailerBundle\Placeholders;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\DotmailerBundle\Transport\DotmailerEmailCampaignTransport;

class EmailCampaignPlaceholderFilter
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
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
                ->getRepository('OroCRMDotmailerBundle:Campaign')
                ->findOneBy(['emailCampaign' => $entity]);

            return (bool) $campaign;
        }

        return false;
    }
}
