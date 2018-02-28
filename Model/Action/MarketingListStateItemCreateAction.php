<?php

namespace Oro\Bundle\DotmailerBundle\Model\Action;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;

class MarketingListStateItemCreateAction extends AbstractMarketingListEntitiesAction
{
    /**
     * @var string
     */
    protected $marketingListStateItemClassName;

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
            /** @var MarketingListStateItemInterface $marketingListStateItem */
            $marketingListStateItem = new $this->marketingListStateItemClassName();

            $entities[] = $marketingListStateItem
                ->setEntityId($marketingListEntity['id'])
                ->setMarketingList($marketingList);
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMarketingListEntitiesByEmailQueryBuilder(MarketingList $marketingList, $email)
    {
        $qb = parent::getMarketingListEntitiesByEmailQueryBuilder($marketingList, $email);

        $qb->leftJoin(
            $this->marketingListStateItemClassName,
            'mli',
            Join::WITH,
            sprintf('mli.entityId = %s.id and mli.marketingList =:marketingList', self::MARKETING_LIST_ENTITY_QB_ALIAS)
        )->setParameter('marketingList', $marketingList);
        $qb->andWhere('mli.id is NULL');

        return $qb->select(sprintf('%s.id as id', self::MARKETING_LIST_ENTITY_QB_ALIAS));
    }
}
