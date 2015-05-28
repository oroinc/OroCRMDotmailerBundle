<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

class RemoveAddressBookWriter implements ItemWriterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $itemsCount = count($items);

        try {
            $em = $this->registry->getManager();
            foreach ($items as $item) {
                $em->remove($item);
            }

            $em->flush();
            $em->clear();

            $this->logger->info("$itemsCount Address Books removed");
        } catch (\Exception $e) {
            $this->logger->error("Removing $itemsCount Address Books failed");
        }
    }
}
