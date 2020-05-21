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

class MarketingListStateItemVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var MarketingListStateItemVoter */
    private $voter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContactInformationFieldsProvider */
    private $contactInformationFieldsProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contactInformationFieldsProvider = $this->createMock(ContactInformationFieldsProvider::class);

        $container = TestContainerBuilder::create()
            ->add('oro_marketing_list.provider.contact_information_fields', $this->contactInformationFieldsProvider)
            ->getContainer($this);

        $this->voter = new MarketingListStateItemVoter(
            $this->doctrineHelper,
            $container,
            Contact::class
        );
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote($identifier, $object, $entity, $expected, $attributes, $queryResult = false)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($identifier));

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
            ->will($this->returnValue(['email']));

        $this->contactInformationFieldsProvider->expects($this->any())
            ->method('getTypedFieldsValues')
            ->will($this->returnValue(['email']));

        $contactRepository->expects($this->any())
            ->method('isUnsubscribedFromAddressBookByMarketingList')
            ->will($this->returnValue($queryResult));

        $this->voter->setClassName(MarketingListUnsubscribedItem::class);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        $item = $this->getItem();
        $entity = new Contact();

        return [
            [null, [], null, MarketingListStateItemVoter::ACCESS_ABSTAIN, []],
            [null, $item, $entity, MarketingListStateItemVoter::ACCESS_ABSTAIN, []],
            [1, $item, $entity, MarketingListStateItemVoter::ACCESS_ABSTAIN, ['VIEW']],
            [1, $item, $entity, MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE']],
            [1, $item, $entity, MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE']],
            [1, $item, $entity, MarketingListStateItemVoter::ACCESS_DENIED, ['DELETE'], true],
            [1, $item, null, MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE'], true],
        ];
    }

    /**
     * @return MarketingListUnsubscribedItem
     */
    private function getItem()
    {
        $item = new MarketingListUnsubscribedItem();
        $marketingList = new MarketingList();

        $item->setMarketingList($marketingList);
        $item->setEntityId(2);
        $marketingList->setEntity(Contact::class);

        return $item;
    }
}
