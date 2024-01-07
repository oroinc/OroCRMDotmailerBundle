<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Iterator for address books remove.
 */
class RemoveAddressBookIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array Array of address book origin Ids
     */
    protected $keepAddressBooks;

    /**
     * @var Channel
     */
    protected $channel;

    public function __construct(ManagerRegistry $registry, Channel $channel, array $keepAddressBooks)
    {
        $this->registry = $registry;
        $this->channel = $channel;
        $this->keepAddressBooks = $keepAddressBooks;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $addressBookForRemoveQB = $this->registry
            ->getRepository(AddressBook::class)
            ->getAddressBooksForRemoveQB($this->channel, $this->keepAddressBooks)
            ->setFirstResult($skip)
            ->setMaxResults($take);

        return $addressBookForRemoveQB
            ->getQuery()
            ->execute();
    }
}
