<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ExportManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        return $this->getAddressBookContactsExportRepostiry()->isExportFinished($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFaultsProcessed(Channel $channel)
    {
        return $this->getAddressBookContactsExportRepostiry()->isExportFaultsProcessed($channel);
    }

    /**
     * @return AddressBookContactsExportRepository
     */
    private function getAddressBookContactsExportRepostiry()
    {
        return $this->managerRegistry->getRepository(AddressBookContactsExport::class);
    }
}
