<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;

class UnsubscribedFromAccountContactIterator extends OverlapIterator
{
    /**
     * @var IResources
     */
    protected $resources;

    /**
     * @var array
     */
    protected $addressBooks;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    public function __construct(IResources $resources, \DateTime $lastSyncDate)
    {
        $this->resources = $resources;
        $this->lastSyncDate = $lastSyncDate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        /** @var ApiContactSuppressionList $contacts */
        $contacts = $this->resources
            ->GetContactsSuppressedSinceDate(
                $this->lastSyncDate->format(\DateTime::ISO8601),
                $take,
                $skip
            );

        return $contacts->toArray();
    }
}
