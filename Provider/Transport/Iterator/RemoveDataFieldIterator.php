<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Iterator for data fields remove.
 */
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

    #[\Override]
    protected function getItems($take, $skip)
    {
        if (!$this->keepDataFieldsNames) {
            //don't remove any data fields if no data came from DM
            return [];
        }

        $dataFieldsForRemoveQB = $this->registry
            ->getRepository(DataField::class)
            ->getDataFieldsForRemoveQB($this->channel, $this->keepDataFieldsNames)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $dataFieldsForRemoveQB
            ->getQuery()
            ->execute();
    }
}
