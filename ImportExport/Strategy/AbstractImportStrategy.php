<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for import strategies
 */
abstract class AbstractImportStrategy implements StrategyInterface, ContextAwareInterface
{
    public const CACHED_CHANNEL = 'cachedChannel';
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        $channelId = $this->context->getOption('channel');
        $channel = $this->cacheProvider->getCachedItem(self::CACHED_CHANNEL, $channelId);
        if (!$channel) {
            $channel = $this->registry
                ->getRepository(Channel::class)
                ->getOrLoadById($channelId);

            $this->cacheProvider->setCachedItem(self::CACHED_CHANNEL, $channelId, $channel);
        }

        return $channel;
    }

    /**
     * @param string $id
     *
     * @return EnumOptionInterface
     */
    protected function getEnumValue(string $id): EnumOptionInterface
    {
        return $this->registry->getRepository(EnumOption::class)->find($id);
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
