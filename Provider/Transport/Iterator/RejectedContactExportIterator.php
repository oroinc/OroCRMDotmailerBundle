<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;

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
            ->getRepository('OroCRMDotmailerBundle:AddressBookContact')
            ->getAddressBookContactsByExportId($this->exportId, $take, $skip);

        return $items;
    }
}
