<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
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
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AddMarketingActivitesActionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MockObject|ContactInformationFieldsProvider */
    protected $contactInformationFieldsProvider;

    /** @var MockObject|MarketingListItemsQueryBuilderProvider */
    protected $marketingListItemsQueryBuilderProvider;

    /** @var MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var MockObject|ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var MockObject|ActivityFactory */
    protected $activityFactory;

    /** @var AddMarketingActivitesAction */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contactInformationFieldsProvider = $this->getMockBuilder(ContactInformationFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->marketingListItemsQueryBuilderProvider = $this
            ->getMockBuilder(MarketingListItemsQueryBuilderProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->activityFactory = $this->getMockBuilder(ActivityFactory::class)->disableOriginalConstructor()->getMock();

        $this->action = new class(
            $this->contextAccessor,
            $this->contactInformationFieldsProvider,
            $this->marketingListItemsQueryBuilderProvider,
            $this->fieldHelper
        ) extends AddMarketingActivitesAction {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        $this->action->setActivityFactory($this->activityFactory);
        $this->action->setDoctrineHelper($this->doctrineHelper);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitialize()
    {
        $options = ['options'];
        static::assertSame($this->action, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    public function testExecuteNotAllowedNotActivityEntity()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $context->expects($this->once())->method('getEntity')->will($this->returnValue(''));
        $this->contextAccessor->expects($this->never())->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedNoCampaign()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $activity =  new Activity();
        $context->expects($this->once())->method('getEntity')->will($this->returnValue($activity));
        $this->contextAccessor->expects($this->never())->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedNoEmailCampaign()
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $campaign = new Campaign();
        $activity =  new Activity();
        $activity->setCampaign($campaign);
        $context->expects($this->once())->method('getEntity')->will($this->returnValue($activity));
        $this->contextAccessor->expects($this->never())->method('getValue');

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
        $context->expects($this->once())->method('getEntity')->will($this->returnValue($activity));
        $this->contextAccessor->expects($this->never())->method('getValue');

        $this->action->execute($context);
    }

    public function testExecuteNotAllowedFeatureDisabled()
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isFeatureEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('marketingactivity', null)
            ->will($this->returnValue(false));
        $context = $this->createMock(EntityAwareInterface::class);
        $context->expects($this->never())->method('getEntity');
        $this->contextAccessor->expects($this->never())->method('getValue');
        $this->action->setFeatureChecker($featureChecker);
        $this->action->addFeature('marketingactivity');

        $this->action->execute($context);
    }

    /**
     * @dataProvider activityDataProvider
     *
     * @param string $unsubscribe
     * @param string $softBounce
     * @param string $hardBounce
     * @param array  $changeSet
     * @param string $expectedType
     */
    public function testExecute($unsubscribe, $softBounce, $hardBounce, $changeSet, $expectedType)
    {
        $context = $this->createMock(EntityAwareInterface::class);
        $campaign = new Campaign();
        $marketingCampaign = new MarketingCampaign();
        $emailCampaign = $this->getEntity(EmailCampaign::class, ['id' => 1]);
        $emailCampaign->setCampaign($marketingCampaign);
        $campaign->setEmailCampaign($emailCampaign);
        $addressBook = $this->getEntity(AddressBook::class, ['id' => 1]);
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
        $context->expects($this->any())->method('getEntity')->will($this->returnValue($activity));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->will($this->returnValue($em));

        $repository = $this->getMockBuilder(ContactRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())->method('getEntitiesDataByOriginIds')->with([$contactOriginId], [1])
            ->will($this->returnValue([
                [
                    'entityClass' => 'EntityClass',
                    'entityId' => 11
                ]
            ]));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Contact::class)
            ->will($this->returnValue($repository));

        $marketingActivity = new MarketingActivity();
        $this->activityFactory->expects($this->at(0))->method('create')->with(
            $marketingCampaign,
            'EntityClass',
            11,
            $activity->getUpdatedAt(),
            MarketingActivity::TYPE_SEND,
            $organization,
            1
        )->will($this->returnValue($marketingActivity));
        $em->expects($this->at(0))->method('persist')->with($marketingActivity);

        if ($expectedType) {
            $anotherMarketingActivity = new MarketingActivity();
            $this->activityFactory->expects($this->at(1))->method('create')->with(
                $marketingCampaign,
                'EntityClass',
                11,
                $activity->getUpdatedAt(),
                $expectedType,
                $organization,
                1
            )->will($this->returnValue($anotherMarketingActivity));
            $em->expects($this->at(1))->method('persist')->with($anotherMarketingActivity);
        }

        $options = [AddMarketingActivitesAction::OPTION_KEY_CHANGESET => $changeSet];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function activityDataProvider()
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
