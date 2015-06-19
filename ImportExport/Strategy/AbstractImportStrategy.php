<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Strategy;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

abstract class AbstractImportStrategy implements StrategyInterface, ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($this->context->getOption('channel'));
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
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $entity
     *
     * @return object
     */
    protected function reattachDetachedEntity($entity)
    {
        $manager = $this->registry->getManager();
        if (!$manager->contains($entity)) {
            return $manager->find(ClassUtils::getClass($entity), $entity);
        }
        return $entity;
    }
}
