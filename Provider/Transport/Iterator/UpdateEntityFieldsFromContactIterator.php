<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

class UpdateEntityFieldsFromContactIterator extends AbstractMarketingListItemIterator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry($registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(AddressBook $addressBook)
    {
        $contactsToUpdateFromQB = $this->registry
            ->getRepository('OroDotmailerBundle:Contact')
            ->getScheduledForEntityFieldsUpdateQB($addressBook);
        if ($addressBook->isCreateEntities()) {
            //if new entities can be added, add subquery to check that no entity with email alreay exists
            $qb = $this->marketingListItemsQueryBuilderProvider->getFindEntityEmailsQB($addressBook);
            $contactsToUpdateFromQB->orWhere(
                $qb->expr()->andX()
                    ->add($qb->expr()->isNull('addressBookContacts.marketingListItemId'))
                    ->add('addressBookContacts.newEntity = :newEntity')
                    ->add($qb->expr()->notIn('contact.email', $qb->getDQL()))
            );
            $contactsToUpdateFromQB->setParameter('newEntity', true);
            if ($parameter = $qb->getParameter('organization')) {
                $contactsToUpdateFromQB->setParameter($parameter->getName(), $parameter->getValue());
            }
        }

        return $contactsToUpdateFromQB;
    }
}
