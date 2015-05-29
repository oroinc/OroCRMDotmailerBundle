<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroCRM\Bundle\DotmailerBundle\Entity\ChannelAwareInterface;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    const PROCESSING_ITEMS = 'processingItems';

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

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
