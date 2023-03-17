<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Placeholders\EmailCampaignPlaceholderFilter;
use Oro\Bundle\DotmailerBundle\Transport\DotmailerEmailCampaignTransport;

class EmailCampaignPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EmailCampaignPlaceholderFilter */
    private $target;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->target = new EmailCampaignPlaceholderFilter($this->registry);
    }

    public function testIsApplicableOnEmailCampaign()
    {
        $actual = $this->target->isApplicableOnEmailCampaign(new \stdClass());
        $this->assertFalse($actual);

        $entity = $this->createMock(EmailCampaign::class);
        $entity->expects($this->once())
            ->method('getTransport')
            ->willReturn('OtherTransport');
        $actual = $this->target->isApplicableOnEmailCampaign($entity);
        $this->assertFalse($actual);

        $entity = $this->createMock(EmailCampaign::class);
        $entity->expects($this->any())
            ->method('getTransport')
            ->willReturn(DotmailerEmailCampaignTransport::NAME);

        $this->repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['emailCampaign' => $entity])
            ->willReturnOnConsecutiveCalls(false, true);

        $actual = $this->target->isApplicableOnEmailCampaign($entity);
        $this->assertFalse($actual);

        $actual = $this->target->isApplicableOnEmailCampaign($entity);
        $this->assertTrue($actual);
    }
}
