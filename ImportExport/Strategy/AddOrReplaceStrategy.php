<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\ChannelAwareInterface;
use Oro\Bundle\DotmailerBundle\Entity\OriginAwareInterface;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Psr\Log\LoggerInterface;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    const BATCH_ITEMS = 'batchItems';
    const CACHED_ADDRESS_BOOK = 'cachedAddressBook';
    const CACHED_CHANNEL = 'cachedChannel';

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param DefaultOwnerHelper $ownerHelper
     */
    public function setOwnerHelper($ownerHelper)
    {
        $this->ownerHelper = $ownerHelper;
    }

    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param object $entity
     *
     * @return object
     */
    protected function beforeProcessEntity($entity)
    {
        $entity = parent::beforeProcessEntity($entity);

        $channel = $this->getChannel();
        $entity->setChannel($channel);

        $this->setOwner($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $entityName = $this->entityName;
        if ($entity instanceof $entityName) {
            return $this->findProcessedEntity($entity, $searchContext);
        } else {
            return parent::findExistingEntity($entity, $searchContext);
        }
    }

    /**
     * @param object $entity
     * @param array  $searchContext
     *
     * @return null|object
     */
    protected function findProcessedEntity($entity, array $searchContext)
    {
        if (!$cacheKey = $this->getCurrentBatchItemsCacheKey($entity)) {
            return parent::findExistingEntity($entity, $searchContext);
        }

        /**
         * Fix case if this entity already imported on this batch and it is new entity
         * Also improve performance for case if it is existing one
         */
        if (!$existingEntity = $this->cacheProvider->getCachedItem(self::BATCH_ITEMS, $cacheKey)) {
            $existingEntity = parent::findExistingEntity($entity, $searchContext);

            $this->cacheProvider->setCachedItem(self::BATCH_ITEMS, $cacheKey, $existingEntity ?: $entity);
        }

        return $existingEntity;
    }

    /**
     * @param object $entity
     *
     * @return int|null
     */
    protected function getCurrentBatchItemsCacheKey($entity)
    {
        if ($entity instanceof OriginAwareInterface) {
            return $entity->getOriginId();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAndUpdateContext($entity)
    {
        if (!$entity) {
            return $entity;
        }

        $entity = parent::validateAndUpdateContext($entity);

        if ($entity && $this->databaseHelper->getIdentifier($entity)) {
            $this->context->incrementUpdateCount();
        }

        return $entity;
    }

    /**
     * @param object $entity
     */
    protected function setOwner($entity)
    {
        if ($entity instanceof ChannelAwareInterface) {
            /** @var Channel $channel */
            $channel = $this->databaseHelper->getEntityReference($entity->getChannel());

            $this->ownerHelper->populateChannelOwner($entity, $channel);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function assertEnvironment($entity)
    {
        if ($entityName = $this->context->getOption('entityName')) {
            $this->entityName = $entityName;
        }

        parent::assertEnvironment($entity);
    }

    /**
     * @param string $entityName "FQCN" or Doctrine entity alias
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository($entityName)
    {
        return $this->strategyHelper
            ->getEntityManager($entityName)
            ->getRepository($entityName);
    }

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        $channelId = $this->context->getOption('channel');
        $channel = $this->cacheProvider->getCachedItem(self::CACHED_CHANNEL, $channelId);
        if (!$channel) {
            $channel = $this->strategyHelper->getEntityManager('OroIntegrationBundle:Channel')
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

        return $this->getRepository($className)
            ->find($id);
    }

    /**
     * @param  int $addressBookOriginId
     *
     * @return null|AddressBook
     */
    protected function getAddressBookByOriginId($addressBookOriginId)
    {
        $addressBook = $this->cacheProvider->getCachedItem(self::CACHED_ADDRESS_BOOK, $addressBookOriginId);
        if (!$addressBook) {
            $addressBook = $this->getRepository('OroDotmailerBundle:AddressBook')
                ->findOneBy(
                    [
                        'channel'  => $this->getChannel(),
                        'originId' => $addressBookOriginId
                    ]
                );

            $this->cacheProvider->setCachedItem(self::CACHED_ADDRESS_BOOK, $addressBookOriginId, $addressBook);
        }

        return $addressBook;
    }
}
