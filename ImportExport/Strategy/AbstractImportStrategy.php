<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerInterface;

abstract class AbstractImportStrategy implements StrategyInterface, ContextAwareInterface
{
    const CACHED_CHANNEL = 'cachedChannel';
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
                ->getRepository('OroIntegrationBundle:Channel')
                ->getOrLoadById($channelId);

            $this->cacheProvider->setCachedItem(self::CACHED_CHANNEL, $channelId, $channel);
        }

        return $channel;
    }

    /**
     * @param string $enumCode
     * @param string $id
     *
     * @return AbstractEnumValue
     */
    protected function getEnumValue($enumCode, $id)
    {
        $className = ExtendHelper::buildEnumValueClassName($enumCode);
        return $this->registry->getRepository($className)
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
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
