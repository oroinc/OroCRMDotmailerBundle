<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Acl\Voter\EmailCampaignVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EmailCampaignVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var EmailCampaignVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new EmailCampaignVoter($this->doctrineHelper);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote($attributes, $emailCampaign, $expected)
    {
        $object = new EmailCampaign();

        $this->voter->setClassName(EmailCampaign::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));
        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($emailCampaign));

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
        $emailCampaignNew = new EmailCampaign();
        $emailCampaignSent = new EmailCampaign();
        $emailCampaignSent->setSent(true);

        return [
            [['VIEW'], $emailCampaignNew, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['CREATE'], $emailCampaignNew, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['EDIT'], $emailCampaignNew, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['DELETE'], $emailCampaignNew, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['ASSIGN'], $emailCampaignNew, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['VIEW'], $emailCampaignSent, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['CREATE'], $emailCampaignSent, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['EDIT'], $emailCampaignSent, EmailCampaignVoter::ACCESS_DENIED],
            [['DELETE'], $emailCampaignSent, EmailCampaignVoter::ACCESS_ABSTAIN],
            [['ASSIGN'], $emailCampaignSent, EmailCampaignVoter::ACCESS_ABSTAIN],
        ];
    }
}
