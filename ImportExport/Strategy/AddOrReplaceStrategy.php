<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroCRM\Bundle\DotmailerBundle\Entity\ChannelAwareInterface;
use OroCRM\Bundle\DotmailerBundle\Entity\OriginAwareInterface;
use OroCRM\Bundle\DotmailerBundle\Provider\CacheProvider;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    const BATCH_ITEMS = 'batchItems';

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);
        $entity = $this->afterProcessAndValidationEntity($entity);
        return $entity;
    }

    protected function afterProcessAndValidationEntity($entity)
    {
        if ($entity instanceof OriginAwareInterface) {
            $this->cacheProvider->setCachedItem(self::BATCH_ITEMS, $entity->getOriginId(), $entity);
        }

        return $entity;
    }

    /**
     * @param DefaultOwnerHelper $ownerHelper
     */
    public function setOwnerHelper($ownerHelper)
    {
        $this->ownerHelper = $ownerHelper;
    }

    /**
     * @param CacheProvider $cacheProvider
     *
     * @return AddOrReplaceStrategy
     */
    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;

        return $this;
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
        $cachedChannel =  $this->context->getValue('cachedChannelEntity');
        if (!$cachedChannel) {
            $channel = $this->strategyHelper->getEntityManager('OroIntegrationBundle:Channel')
                ->getRepository('OroIntegrationBundle:Channel')
                ->getOrLoadById($this->context->getOption('channel'));

            $this->context->setValue('cachedChannelEntity', $channel);
        } else {
            $this->context->setValue('cachedChannelEntity', $this->reattachDetachedEntity($cachedChannel));
        }

        return $this->context->getValue('cachedChannelEntity');
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
     * @param object $entity
     *
     * @return object
     */
    protected function reattachDetachedEntity($entity)
    {
        $entityClassName = ClassUtils::getClass($entity);
        $manager = $this->strategyHelper
            ->getEntityManager($entityClassName);
        if (!$manager->contains($entity)) {
            return $manager->find($entityClassName, $entity);
        }
        return $entity;
    }
}
