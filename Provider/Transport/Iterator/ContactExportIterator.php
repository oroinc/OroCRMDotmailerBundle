<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ContactExportIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ObjectRepository
     */
    protected $addressBookContactRepository;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param ManagerRegistry $registry
     * @param Channel         $channel
     */
    public function __construct(ManagerRegistry $registry, Channel $channel)
    {
        $this->registry = $registry;
        $this->addressBookContactRepository = $registry->getRepository('OroCRMDotmailerBundle:AddressBookContact');
        $this->channel = $channel;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        return $this->addressBookContactRepository
            ->getScheduledForExportByChannelQB($this->channel)
            ->setFirstResult($skip)
            ->setMaxResults($take);
    }
}
