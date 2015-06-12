<?php

namespace OroCRM\Bundle\DotmailerBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroCRM\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
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
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        FieldHelper $fieldHelper,
        $contactClassName
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->fieldHelper = $fieldHelper;
        $this->contactClassName = $contactClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var MarketingListStateItemInterface $item */
        $item = $this->doctrineHelper
            ->getEntityRepository($this->className)
            ->find($identifier);
        $entityClass = $item->getMarketingList()->getEntity();
        $entity = $this->doctrineHelper
            ->getEntityRepository($entityClass)
            ->find($item->getEntityId());

        if (!$entity) {
            return self::ACCESS_ABSTAIN;
        }

        $contactInformationFields = $this->contactInformationFieldsProvider
            ->getEntityTypedFields(
                $entityClass,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            );

        $contactInformationValues = $this->contactInformationFieldsProvider
            ->getTypedFieldsValues(
                $contactInformationFields,
                $entity
            );

        /** @var ContactRepository $contactRepository */
        $contactRepository = $this->doctrineHelper
            ->getEntityRepository($this->contactClassName);
        $result = $contactRepository->isUnsubscribedFromAddressBookByMarketingList(
            $contactInformationValues,
            $item->getMarketingList()
        );

        return $result ? self::ACCESS_DENIED : self::ACCESS_ABSTAIN;
    }
}
