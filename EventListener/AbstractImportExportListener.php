<?php

namespace Oro\Bundle\DotmailerBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractImportExportListener implements EventSubscriberInterface
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
}
