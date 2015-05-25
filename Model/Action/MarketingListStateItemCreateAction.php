<?php

namespace OroCRM\Bundle\DotmailerBundle\Model\Action;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;

class MarketingListStateItemCreateAction extends AbstractMarketingListEntitiesAction
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $marketingListStateItemClassName;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper($doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $marketingListStateItemClassName
     */
    public function setMarketingListStateItemClassName($marketingListStateItemClassName)
    {
        $this->marketingListStateItemClassName = $marketingListStateItemClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entities = $this->getMarketingListStateItems($context->getEntity());
        if (count($entities) == 0) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($this->marketingListStateItemClassName);
        foreach ($entities as $entity) {
            $em->persist($entity);
        }

        $em->flush($entities);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$this->doctrineHelper) {
            throw new \InvalidArgumentException('DoctrineHelper is not provided');
        }

        if (!$this->marketingListStateItemClassName) {
            throw new \InvalidArgumentException('marketingListStateItemClassName is not provided');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitiesQueryBuilder(MarketingList $marketingList)
    {
        $className = $marketingList->getEntity();

        $qb = $this->doctrineHelper
            ->getEntityManager($className)
            ->getRepository($className)
            ->createQueryBuilder('e');

        return $qb;
    }

    /**
     * @param AddressBookContact $abContact
     * @return MarketingListStateItemInterface[]
     */
    protected function getMarketingListStateItems(AddressBookContact $abContact)
    {
        $entities = [];

        $marketingList = $abContact->getAddressBook()->getMarketingList();
        if (!$marketingList) {
            return $entities;
        }
        $marketingListEntities = $this->getMarketingListEntitiesByEmail(
            $marketingList,
            $abContact->getContact()->getEmail()
        );

        foreach ($marketingListEntities as $marketingListEntity) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($marketingListEntity);

            $criteria = [
                'entityId' => $entityId,
                'marketingList' => $marketingList->getId()
            ];

            if ($this->getMarketingListStateItem($criteria)) {
                continue;
            }

            /** @var MarketingListStateItemInterface $marketingListStateItem */
            $marketingListStateItem = new $this->marketingListStateItemClassName();

            $marketingListStateItem
                ->setEntityId($entityId)
                ->setMarketingList($marketingList);

            $entities[] = $marketingListStateItem;
        }

        return $entities;
    }

    /**
     * @param array $criteria
     * @return MarketingListStateItemInterface|null
     */
    protected function getMarketingListStateItem(array $criteria)
    {
        return $this->doctrineHelper
            ->getEntityManager($this->marketingListStateItemClassName)
            ->getRepository($this->marketingListStateItemClassName)
            ->findOneBy($criteria);
    }
}
