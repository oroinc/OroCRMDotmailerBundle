<?php

namespace Oro\Bundle\DotmailerBundle\Placeholders;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Filters marketing list placeholders.
 */
class MarketingListPlaceholderFilter
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
     * @param MarketingList $marketingList
     * @return bool
     */
    public function isApplicableOnMarketingList($marketingList)
    {
        if ($marketingList instanceof MarketingList) {
            return (bool)$this->registry
                ->getRepository(AddressBook::class)
                ->findOneBy(['marketingList' => $marketingList]);
        }

        return false;
    }
}
