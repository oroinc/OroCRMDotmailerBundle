<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class StateAwareCacheProvider extends CacheProvider
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     *
     * If entity is detached refresh it together with all related entities (some kind of lazy loading)
     */
    public function getCachedItem($scopeKey, $itemKey)
    {
        if (!isset($this->itemsCache[$scopeKey][$itemKey])) {
            return null;
        }

        $item = $this->itemsCache[$scopeKey][$itemKey];
        if (!is_object($item)) {
            return $item;
        }

        if (!$this->doctrineHelper->isManageableEntity($item)) {
            return $item;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManager($item);
        if ($em->getUnitOfWork()->getEntityState($item) === UnitOfWork::STATE_DETACHED) {
            $this->doctrineHelper->refreshIncludingUnitializedRelations($item);
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     *
     * Do not store entity itself, save reference only
     */
    public function setCachedItem($scopeKey, $itemKey, $value)
    {
        if (!is_object($value)) {
            $this->itemsCache[$scopeKey][$itemKey] = $value;

            return $this;
        }

        if (!$this->doctrineHelper->isManageableEntity($value)) {
            $this->itemsCache[$scopeKey][$itemKey] = $value;

            return $this;
        }

        $id = $this->doctrineHelper->getSingleEntityIdentifier($value);
        if ($id) {
            $class = $this->doctrineHelper->getEntityClass($value);
            $this->itemsCache[$scopeKey][$itemKey] = $this->doctrineHelper->getEntityReference($class, $id);
        } else {
            $this->itemsCache[$scopeKey][$itemKey] = $value;
        }

        return $this;
    }
}
