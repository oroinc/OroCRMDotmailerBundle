<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class RemoveDataFieldIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array Array of data fields names from origin
     */
    protected $keepDataFieldsNames;

    /**
     * @var Channel
     */
    protected $channel;

    public function __construct(ManagerRegistry $registry, Channel $channel, array $keepDataFieldsNames)
    {
        $this->registry = $registry;
        $this->channel = $channel;
        $this->keepDataFieldsNames = $keepDataFieldsNames;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        if (!$this->keepDataFieldsNames) {
            //don't remove any data fields if no data came from DM
            return [];
        }

        $dataFieldsForRemoveQB = $this->registry
            ->getRepository('OroDotmailerBundle:DataField')
            ->getDataFieldsForRemoveQB($this->channel, $this->keepDataFieldsNames)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $dataFieldsForRemoveQB
            ->getQuery()
            ->execute();
    }
}
