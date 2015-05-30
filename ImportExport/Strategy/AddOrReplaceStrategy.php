<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroCRM\Bundle\DotmailerBundle\Entity\ChannelAwareInterface;
use OroCRM\Bundle\DotmailerBundle\Entity\OriginAwareInterface;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    const BATCH_ITEMS = 'batchItems';

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);

        if ($entity instanceof OriginAwareInterface) {
            $batchItems = $this->context->getValue(self::BATCH_ITEMS)?:[];
            $batchItems[$entity->getOriginId()] = $entity;
            $this->context->setValue(self::BATCH_ITEMS, $batchItems);
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
        $channel = $this->strategyHelper->getEntityManager('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
        return $channel;
    }
}
