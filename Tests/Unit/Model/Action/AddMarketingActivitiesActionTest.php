<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CampaignBundle\Entity\Campaign as MarketingCampaign;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Model\Action\AddMarketingActivitesAction;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;
use Oro\Bundle\MarketingActivityBundle\Model\ActivityFactory;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AddMarketingActivitiesActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ActivityFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $activityFactory;

    /** @var AddMarketingActivitesAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->activityFactory = $this->createMock(ActivityFactory::class);

        $this->action = new AddMarketingActivitesAction(
            $this->contextAccessor,
            $this->createMock(ContactInformationFieldsProvider::class),
            $this->createMock(MarketingListItemsQueryBuilderProvider::class),
            $this->createMock(FieldHelper::class)
        );

        $this->action->setActivityFactory($this->activityFactory);
        $this->action->setDoctrineHelper($this->doctrineHelper);

        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitialize()
    {
        $options = ['options'];
        self::assertSame($this->action, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function testExecuteNotAllowedNotActivityEntity()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $context->expects($this->once())
            ->method('getEntity')
            ->willReturn('');
        $this->contextAccessor->expects($this->never())
            ->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedNoCampaign()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $activity =  new Activity();
        $context->expects($this->once())
            ->method('getEntity')
            ->willReturn($activity);
        $this->contextAccessor->expects($this->never())
            ->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedNoEmailCampaign()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $campaign = new Campaign();
        $activity =  new Activity();
        $activity->setCampaign($campaign);
        $context->expects($this->once())
            ->method('getEntity')
            ->willReturn($activity);
        $this->contextAccessor->expects($this->never())
            ->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedNoMarketingCampaign()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $campaign = new Campaign();
        $emailCampaign = new EmailCampaign();
        $campaign->setEmailCampaign($emailCampaign);
        $activity =  new Activity();
        $activity->setCampaign($campaign);
        $context->expects($this->once())
            ->method('getEntity')
            ->willReturn($activity);
        $this->contextAccessor->expects($this->never())
            ->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedFeatureDisabled()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('marketingactivity', null)
            ->willReturn(false);
        $context = $this->createMock(EntityAwareInterface::class);
        $context->expects($this->never())
            ->method('getEntity');
        $this->contextAccessor->expects($this->never())
            ->method('getValue');
        $this->action->setFeatureChecker($featureChecker);
        $this->action->addFeature('marketingactivity');

        $this->action->execute($context);
    }

    /**
     * @dataProvider activityDataProvider
     */
    public function testExecute(
        bool $unsubscribe,
        bool $softBounce,
        bool $hardBounce,
        ?array $changeSet,
        ?string $expectedType
    ) {
        $context = $this->createMock(EntityAwareInterface::class);
        $campaign = new Campaign();
        $marketingCampaign = new MarketingCampaign();
        $emailCampaign = new EmailCampaign();
        ReflectionUtil::setId($emailCampaign, 1);
        $emailCampaign->setCampaign($marketingCampaign);
        $campaign->setEmailCampaign($emailCampaign);
        $addressBook = new AddressBook();
        ReflectionUtil::setId($addressBook, 1);
        $campaign->setAddressBooks(new ArrayCollection([$addressBook]));
        $updatedAt = new \DateTime();
        $activity =  new Activity();
        $activity->setCampaign($campaign);
        $activity->setUpdatedAt($updatedAt);
        $activity->setDateSent($updatedAt);
        $activity->setUnsubscribed($unsubscribe);
        $activity->setSoftBounced($softBounce);
        $activity->setHardBounced($hardBounce);
        $organization = new Organization();
        $activity->setOwner($organization);
        $contact = new Contact();
        $contactOriginId = 123;
        $contact->setOriginId($contactOriginId);
        $activity->setContact($contact);
        $context->expects($this->any())
            ->method('getEntity')
            ->willReturn($activity);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($em);

        $repository = $this->createMock(ContactRepository::class);

        $repository->expects($this->once())
            ->method('getEntitiesDataByOriginIds')
            ->with([$contactOriginId], [1])
            ->willReturn([
                [
                    'entityClass' => 'EntityClass',
                    'entityId'    => 11
                ]
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Contact::class)
            ->willReturn($repository);

        $createExpectations = [
            [
                $marketingCampaign,
                'EntityClass',
                11,
                $activity->getUpdatedAt(),
                MarketingActivity::TYPE_SEND,
                $organization,
                1
            ]
        ];
        $createExpectationsResult = [new MarketingActivity()];
        if ($expectedType) {
            $createExpectations[] = [
                $marketingCampaign,
                'EntityClass',
                11,
                $activity->getUpdatedAt(),
                $expectedType,
                $organization,
                1
            ];
            $createExpectationsResult[] = new MarketingActivity();
        }
        $this->activityFactory->expects($this->exactly(count($createExpectations)))
            ->method('create')
            ->withConsecutive(...$createExpectations)
            ->willReturnOnConsecutiveCalls(...$createExpectationsResult);
        $em->expects($this->exactly(count($createExpectations)))
            ->method('persist')
            ->withConsecutive(...array_map(
                function ($item) {
                    return [$item];
                },
                $createExpectationsResult
            ));

        $options = [AddMarketingActivitesAction::OPTION_KEY_CHANGESET => $changeSet];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function activityDataProvider(): array
    {
        return [
            'send new' => [
                'unsubscribe' => false,
                'soft_bounce' => false,
                'hard_bounce' => false,
                'change_set'  => null,
                'expected' => null
            ],
            'unsubscribe new' => [
                'unsubscribe' => true,
                'soft_bounce' => false,
                'hard_bounce' => false,
                'change_set'  => null,
                'expected' => MarketingActivity::TYPE_UNSUBSCRIBE
            ],
            'soft_bounce new' => [
                'unsubscribe' => false,
                'soft_bounce' => true,
                'hard_bounce' => false,
                'change_set'  => null,
                'expected' => MarketingActivity::TYPE_SOFT_BOUNCE,
            ],
            'hard_bounce new' => [
                'unsubscribe' => false,
                'soft_bounce' => false,
                'hard_bounce' => true,
                'change_set'  => null,
                'expected' => MarketingActivity::TYPE_HARD_BOUNCE,
            ],
            'send update' => [
                'unsubscribe' => false,
                'soft_bounce' => false,
                'hard_bounce' => false,
                'change_set'  => ['dateSend' => ['old' => new \DateTime(), 'new' => new \DateTime('tomorrow')]],
                'expected' => null
            ],
            'unsubscribe update' => [
                'unsubscribe' => true,
                'soft_bounce' => false,
                'hard_bounce' => false,
                'change_set'  => ['unsubscribe' => ['old' => 0, 'new' => 1]],
                'expected' => MarketingActivity::TYPE_UNSUBSCRIBE
            ],
            'soft_bounce update' => [
                'unsubscribe' => false,
                'soft_bounce' => true,
                'hard_bounce' => false,
                'change_set'  => ['soft_bounce' => ['old' => 0, 'new' => 1]],
                'expected' => MarketingActivity::TYPE_SOFT_BOUNCE
            ],
            'hard_bounce update' => [
                'unsubscribe' => false,
                'soft_bounce' => false,
                'hard_bounce' => true,
                'change_set'  => ['hard_bounce' => ['old' => 0, 'new' => 1]],
                'expected' => MarketingActivity::TYPE_HARD_BOUNCE
            ]
        ];
    }
}
