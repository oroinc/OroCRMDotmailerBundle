<?php

namespace Oro\Bundle\DotmailerBundle\Model\Action;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;

class MarketingListStateItemRemoveAction extends AbstractMarketingListEntitiesAction
{
    const MARKETING_LIST_STATE_ITEM_ID_ALIAS = 'marketingListStateItemId';

    /**
     * @var string
     */
    protected $marketingListStateItemClassName;

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
            $em->remove($entity);
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
     *
     * @return MarketingListStateItemInterface[]
     */
    protected function getMarketingListStateItems(AddressBookContact $abContact)
    {
        $marketingList = $abContact->getAddressBook()->getMarketingList();
        if (!$marketingList) {
            return [];
        }

        $entities = $this->getMarketingListEntitiesByEmail(
            $marketingList,
            $abContact->getContact()->getEmail()
        );

        $em = $this->doctrineHelper->getEntityManager($this->marketingListStateItemClassName);

        $marketingListStateItems = [];
        foreach ($entities as $entity) {
            $marketingListStateItems[] = $em->getPartialReference(
                $this->marketingListStateItemClassName,
                $entity[self::MARKETING_LIST_STATE_ITEM_ID_ALIAS]
            );
        }

        return $marketingListStateItems;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMarketingListEntitiesByEmailQueryBuilder(MarketingList $marketingList, $email)
    {
        $qb = parent::getMarketingListEntitiesByEmailQueryBuilder($marketingList, $email);

        $qb->innerJoin(
            $this->marketingListStateItemClassName,
            'mli',
            Join::WITH,
            sprintf('mli.entityId = %s.id and mli.marketingList =:marketingList', self::MARKETING_LIST_ENTITY_QB_ALIAS)
        )->setParameter('marketingList', $marketingList);

        return $qb->select(
            sprintf(
                "mli.id as %s",
                self::MARKETING_LIST_STATE_ITEM_ID_ALIAS
            )
        );
    }

    /**
     * @param string $marketingListStateItemClassName
     */
    public function setMarketingListStateItemClassName($marketingListStateItemClassName)
    {
        $this->marketingListStateItemClassName = $marketingListStateItemClassName;
    }
}
