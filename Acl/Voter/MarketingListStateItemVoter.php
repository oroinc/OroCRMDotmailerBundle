<?php

namespace Oro\Bundle\DotmailerBundle\Acl\Voter;

use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Checks whether MarketingListUnsubscribedItem entity can be deleted.
 */
class MarketingListStateItemVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::DELETE];

    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $contactClassName;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContainerInterface $container,
        string $contactClassName
    ) {
        parent::__construct($doctrineHelper);
        $this->container = $container;
        $this->contactClassName = $contactClassName;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_marketing_list.provider.contact_information_fields' => ContactInformationFieldsProvider::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var MarketingListStateItemInterface $item */
        $item = $this->findEntity($this->className, $identifier);
        $entityClass = $item->getMarketingList()->getEntity();
        $entity = $this->findEntity($entityClass, $item->getEntityId());

        if (!$entity) {
            return self::ACCESS_ABSTAIN;
        }

        /** @var ContactRepository $contactRepository */
        $contactRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->contactClassName);
        $unsubscribed = $contactRepository->isUnsubscribedFromAddressBookByMarketingList(
            $this->getContactInformationValues($entityClass, $entity),
            $item->getMarketingList()
        );

        return $unsubscribed
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    private function findEntity(string $entityClass, $entityId)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->find($entityId);
    }

    /**
     * @param string $entityClass
     * @param object $entity
     *
     * @return array
     */
    private function getContactInformationValues(string $entityClass, $entity): array
    {
        /** @var ContactInformationFieldsProvider $fieldsProvider */
        $fieldsProvider = $this->container->get('oro_marketing_list.provider.contact_information_fields');

        return $fieldsProvider->getTypedFieldsValues(
            $fieldsProvider->getEntityTypedFields(
                $entityClass,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            ),
            $entity
        );
    }
}
