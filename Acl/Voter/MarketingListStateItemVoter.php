<?php

namespace OroCRM\Bundle\DotmailerBundle\Acl\Voter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class MarketingListStateItemVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = ['DELETE'];

    /**
     * @var ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var string
     */
    protected $contactClassName;

    /**
     * @var string
     */
    protected $addressBookClassName;

    /**
     * @var string
     */
    protected $marketingListClassName;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param FieldHelper $fieldHelper
     * @param string $contactClassName
     * @param string $addressBookClassName
     * @param string $abContactClassName
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        FieldHelper $fieldHelper,
        $contactClassName,
        $addressBookClassName,
        $abContactClassName
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->fieldHelper = $fieldHelper;
        $this->contactClassName = $contactClassName;
        $this->addressBookClassName = $addressBookClassName;
        $this->abContactClassName = $abContactClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var MarketingListStateItemInterface $item */
        $item = $this->doctrineHelper->getEntityRepository($this->className)->find($identifier);
        $entityClass = $item->getMarketingList()->getEntity();
        $entity = $this->doctrineHelper->getEntityRepository($entityClass)->find($item->getEntityId());

        if (!$entity) {
            return self::ACCESS_ABSTAIN;
        }

        $contactInformationFields = $this->contactInformationFieldsProvider->getEntityTypedFields(
            $entityClass,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        $contactInformationValues = $this->contactInformationFieldsProvider->getTypedFieldsValues(
            $contactInformationFields,
            $entity
        );

        $qb = $this->getQueryBuilder($contactInformationValues, $item);

        $result = $qb->getQuery()->getScalarResult();

        if (!empty($result)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param array $contactInformationValues
     * @param MarketingListStateItemInterface $item
     * @return QueryBuilder
     */
    protected function getQueryBuilder(array $contactInformationValues, $item)
    {
        $qb = $this->doctrineHelper
            ->getEntityManager($this->abContactClassName)
            ->createQueryBuilder();

        $qb
            ->select('COUNT(dmAbContact.id)')
            ->from($this->abContactClassName, 'dmAbContact')
            ->join(
                $this->addressBookClassName,
                'addressBook',
                Join::WITH,
                'dmAbContact.addressBook = addressBook.id'
            )
            ->join(
                $this->contactClassName,
                'dmContact',
                Join::WITH,
                'dmAbContact.contact = dmContact.id'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('addressBook.marketingList', $item->getMarketingList()),
                    $qb->expr()->in('dmContact.email', $contactInformationValues),
                    $qb->expr()->eq('dmAbContact.status', ':status')
                )
            )
            ->setParameter('status', Contact::STATUS_UNSUBSCRIBED);

        return $qb;
    }
}
