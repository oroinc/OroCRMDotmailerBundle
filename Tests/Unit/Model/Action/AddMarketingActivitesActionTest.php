<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CampaignBundle\Entity\Campaign as MarketingCampaign;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\Contact;
use Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository;
use Oro\Bundle\DotmailerBundle\Model\Action\AddMarketingActivitesAction;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\EnumValueStub;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;

class AddMarketingActivitesActionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $enumProvider;

    /**
     * @var AddMarketingActivitesAction
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->enumProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new AddMarketingActivitesAction($this->contextAccessor, $this->registry, $this->enumProvider);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testInitialize()
    {
        $options = ['options'];
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options, 'options', $this->action);
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
}
