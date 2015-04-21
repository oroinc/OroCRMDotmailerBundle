<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use OroCRM\Bundle\DotmailerBundle\Entity\OriginAwareInterface;

class AddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @param object $entity
     * @return object
     */
    protected function beforeProcessEntity($entity)
    {
        /** @var OriginAwareInterface $entity */
        $entity = parent::beforeProcessEntity($entity);

        $channel = $this->strategyHelper->getEntityManager('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
        $entity->setChannel($channel);

        return $entity;
    }

    /**
     * @param $entity
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
}
