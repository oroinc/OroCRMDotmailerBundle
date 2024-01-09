<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;

/**
 * Iterator for rejected contacts export.
 */
class RejectedContactExportIterator extends AbstractIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var string
     */
    protected $exportId;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $exportId
     */
    public function __construct(ManagerRegistry $managerRegistry, $exportId)
    {
        $this->managerRegistry = $managerRegistry;
        $this->exportId = $exportId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($take, $skip)
    {
        $items = $this->managerRegistry
            ->getRepository(AddressBookContact::class)
            ->getAddressBookContactsByExportId($this->exportId, $take, $skip);

        return $items;
    }
}
