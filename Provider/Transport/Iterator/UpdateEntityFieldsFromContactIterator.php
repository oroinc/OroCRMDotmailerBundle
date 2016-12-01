<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class UpdateEntityFieldsFromContactIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param ManagerRegistry $registry
     * @param Channel $channel
     */
    public function __construct(ManagerRegistry $registry, Channel $channel)
    {
        $this->registry = $registry;
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $contactsToUpdateFromQB = $this->registry
            ->getRepository('OroDotmailerBundle:Contact')
            ->getScheduledForEntityFieldsUpdateQB($this->channel)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $contactsToUpdateFromQB
            ->getQuery()
            ->execute();
    }
}
