<?php

namespace Oro\Bundle\DotmailerBundle\Acl\Voter;

use Oro\Bundle\DotmailerBundle\Entity\Contact;
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
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    private ContainerInterface $container;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
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
     * {@inheritDoc}
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
        $contactRepository = $this->doctrineHelper->getEntityRepositoryForClass(Contact::class);
        $unsubscribed = $contactRepository->isUnsubscribedFromAddressBookByMarketingList(
            $this->getContactInformationValues($entityClass, $entity),
            $item->getMarketingList()
        );

        return $unsubscribed
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function findEntity(string $entityClass, mixed $entityId): ?object
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->find($entityId);
    }

    private function getContactInformationValues(string $entityClass, object $entity): array
    {
        $fieldsProvider = $this->getContactInformationFieldsProvider();

        return $fieldsProvider->getTypedFieldsValues(
            $fieldsProvider->getEntityTypedFields(
                $entityClass,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            ),
            $entity
        );
    }

    private function getContactInformationFieldsProvider(): ContactInformationFieldsProvider
    {
        return $this->container->get('oro_marketing_list.provider.contact_information_fields');
    }
}
