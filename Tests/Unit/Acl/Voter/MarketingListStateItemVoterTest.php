<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\DotmailerBundle\Acl\Voter\MarketingListStateItemVoter;
use Oro\Bundle\DotmailerBundle\Model\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MarketingListStateItemVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MarketingListStateItemVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper
     */
    protected $fieldHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactInformationFieldsProvider = $this->getMockBuilder(
            'Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Model\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new MarketingListStateItemVoter(
            $this->doctrineHelper,
            $this->contactInformationFieldsProvider,
            $this->fieldHelper,
            'Oro\Bundle\DotmailerBundle\Entity\Contact'
        );
    }

    /**
     * @param mixed $identifier
     * @param mixed $className
     * @param mixed $object
     * @param bool  $expected
     * @param array $attributes
     * @param bool  $queryResult
     *
     * @dataProvider attributesDataProvider
     */
    public function testVote($identifier, $className, $object, $expected, $attributes, $queryResult = false)
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnValue($identifier));

        $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $contactRepository = $this->getMockBuilder(
            'Oro\Bundle\DotmailerBundle\Entity\Repository\ContactRepository'
        )
            ->setMethods(['isUnsubscribedFromAddressBookByMarketingList'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('find')
            ->will(
                $this->returnValueMap(
                    [
                        [$identifier, $this->getItem()],
                        [2, $object]
                    ]
                )
            );

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['stdClass', $repository],
                        ['Oro\Bundle\DotmailerBundle\Entity\Contact', $contactRepository],
                    ]
                )
            );

        if (is_object($object)) {
            $this->doctrineHelper
                ->expects($this->any())
                ->method('getEntityClass')
                ->will($this->returnValue(get_class($object)));
        }

        $this->contactInformationFieldsProvider
            ->expects($this->any())
            ->method('getEntityTypedFields')
            ->will($this->returnValue(['email']));

        $this->contactInformationFieldsProvider
            ->expects($this->any())
            ->method('getTypedFieldsValues')
            ->will($this->returnValue(['email']));

        $contactRepository
            ->expects($this->any())
            ->method('isUnsubscribedFromAddressBookByMarketingList')
            ->will($this->returnValue($queryResult));

        $this->voter->setClassName($className);

        /** @var TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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
        return [
            [null, null, [], MarketingListStateItemVoter::ACCESS_ABSTAIN, []],
            [null, null, new \stdClass(), MarketingListStateItemVoter::ACCESS_ABSTAIN, []],
            [1, null, new \stdClass(), MarketingListStateItemVoter::ACCESS_ABSTAIN, ['VIEW']],
            [1, 'NotSupports', new \stdClass(), MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE']],
            [1, 'stdClass', new \stdClass(), MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE']],
            [1, 'stdClass', new \stdClass(), MarketingListStateItemVoter::ACCESS_DENIED, ['DELETE'], true],
            [1, 'stdClass', null, MarketingListStateItemVoter::ACCESS_ABSTAIN, ['DELETE'], true],
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getItem()
    {
        $item = $this->createMock('Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface');
        $marketingList = $this->createMock('Oro\Bundle\MarketingListBundle\Entity\MarketingList');

        $item
            ->expects($this->any())
            ->method('getMarketingList')
            ->will($this->returnValue($marketingList));

        $item
            ->expects($this->any())
            ->method('getEntityId')
            ->will($this->returnValue(2));

        $marketingList
            ->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue('stdClass'));

        return $item;
    }
}
