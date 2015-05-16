<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class ContactExportIterator
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
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->addressBookContactRepository = $registry->getRepository('OroCRMDotmailerBundle:AddressBookContact');
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        return $this->addressBookContactRepository->findBy(
            ['scheduledForExport' => true],
            ['addressBook'],
            $take,
            $skip
        );
    }
}
