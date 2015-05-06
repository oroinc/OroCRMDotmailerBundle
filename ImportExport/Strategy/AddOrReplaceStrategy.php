<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroCRM\Bundle\DotmailerBundle\Entity\ChannelAwareInterface;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
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

        $channel = $this->strategyHelper->getEntityManager('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
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

        return parent::validateAndUpdateContext($entity);
    }

    /**
     * @param $entity
     *
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function refreshEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        $manager = $this->strategyHelper->getEntityManager($entityClass);
        if (!$manager->contains($entity)) {
            $identity = $this->databaseHelper->getIdentifier($entity);
            return $manager->find($entityClass, $identity);
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
}
