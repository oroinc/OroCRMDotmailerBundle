<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;

abstract class AbstractImportExportListener implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param SyncEvent $syncEvent
     * @param string $job
     *
     * @return bool
     */
    protected function isApplicable(SyncEvent $syncEvent, $job)
    {
        return $syncEvent->getJobName() == $job
                && $syncEvent->getJobResult() && $syncEvent->getJobResult()->isSuccessful();
    }

    /**
     * @param array $configuration
     *
     * @return Channel
     * @throws RuntimeException
     */
    protected function getChannel(array $configuration)
    {
        if (empty($configuration['import']['channel'])) {
            throw new RuntimeException('Integration channel Id required');
        }
        $channel = $this->registry
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($configuration['import']['channel']);
        if (!$channel) {
            throw new RuntimeException('Integration channel is not exist');
        }

        return $channel;
    }

    /**
     * @param array $configuration
     *
     * @return AddressBook
     * @throws RuntimeException
     */
    protected function getAddressBook(array $configuration)
    {
        if (empty($configuration['import'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION])) {
            throw new RuntimeException('Address Book Id required');
        }
        $addressBook = $this->registry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->find($configuration['import'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION]);
        if (!$addressBook) {
            throw new RuntimeException('Address Book is not exist');
        }

        return $addressBook;
    }
}
