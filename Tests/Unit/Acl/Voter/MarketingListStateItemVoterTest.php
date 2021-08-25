<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DotmailerBundle\Acl\Voter\MarketingListStateItemVoter;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListUnsubscribedItem;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MarketingListStateItemVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ContactInformationFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contactInformationFieldsProvider;

    /** @var MarketingListStateItemVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contactInformationFieldsProvider = $this->createMock(ContactInformationFieldsProvider::class);

        $container = TestContainerBuilder::create()
            ->add('oro_marketing_list.provider.contact_information_fields', $this->contactInformationFieldsProvider)
            ->getContainer($this);

        $this->voter = new MarketingListStateItemVoter($this->doctrineHelper, $container);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(
        $identifier,
        mixed $object,
        ?Contact $entity,
        int $expected,
        array $attributes,
        bool $queryResult = false
    ) {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn($identifier);

        $repository = $this->createMock(EntityRepository::class);
        $contactRepository = $this->createMock(ContactRepository::class);

        $repository->expects($this->any())
            ->method('find')
            ->with($identifier)
            ->willReturn($object);
        $contactRepository->expects($this->any())
            ->method('find')
            ->with(2)
            ->willReturn($entity);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [MarketingListUnsubscribedItem::class, $repository],
                [Contact::class, $contactRepository],
            ]);

        $this->contactInformationFieldsProvider->expects($this->any())
            ->method('getEntityTypedFields')
            ->willReturn(['email']);

        $this->contactInformationFieldsProvider->expects($this->any())
            ->method('getTypedFieldsValues')
            ->willReturn(['email']);

        $contactRepository->expects($this->any())
            ->method('isUnsubscribedFromAddressBookByMarketingList')
            ->willReturn($queryResult);

        $this->voter->setClassName(MarketingListUnsubscribedItem::class);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    public function attributesDataProvider(): array
    {
        $item = $this->getItem();
        $entity = new Contact();

        return [
            [null, [], null, VoterInterface::ACCESS_ABSTAIN, []],
            [null, $item, $entity, VoterInterface::ACCESS_ABSTAIN, []],
            [1, $item, $entity, VoterInterface::ACCESS_ABSTAIN, ['VIEW']],
            [1, $item, $entity, VoterInterface::ACCESS_ABSTAIN, ['DELETE']],
            [1, $item, $entity, VoterInterface::ACCESS_ABSTAIN, ['DELETE']],
            [1, $item, $entity, VoterInterface::ACCESS_DENIED, ['DELETE'], true],
            [1, $item, null, VoterInterface::ACCESS_ABSTAIN, ['DELETE'], true],
        ];
    }

    private function getItem(): MarketingListUnsubscribedItem
    {
        $item = new MarketingListUnsubscribedItem();
        $marketingList = new MarketingList();

        $item->setMarketingList($marketingList);
        $item->setEntityId(2);
        $marketingList->setEntity(Contact::class);

        return $item;
    }
}
