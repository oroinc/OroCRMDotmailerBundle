<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use OroCRM\Bundle\DotmailerBundle\Acl\Voter\MarketingListStateItemVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\DotmailerBundle\Model\FieldHelper;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class MarketingListStateItemVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MarketingListStateItemVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FieldHelper
     */
    protected $fieldHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactInformationFieldsProvider = $this->getMockBuilder(
            'OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldHelper = $this->getMockBuilder('OroCRM\Bundle\DotmailerBundle\Model\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new MarketingListStateItemVoter(
            $this->doctrineHelper,
            $this->contactInformationFieldsProvider,
            $this->fieldHelper,
            'OroCRM\Bundle\DotmailerBundle\Entity\Contact'
        );
    }

    /**
     * @param string $attribute
     * @param bool   $expected
     *
     * @dataProvider supportsAttributeDataProvider
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    /**
     * @return array
     */
    public function supportsAttributeDataProvider()
    {
        return [
            'VIEW'   => ['VIEW', false],
            'CREATE' => ['CREATE', false],
            'EDIT'   => ['EDIT', false],
            'DELETE' => ['DELETE', true],
            'ASSIGN' => ['ASSIGN', false],
        ];
    }

    /**
     * @param string $class
     * @param string $actualClass
     * @param bool   $expected
     *
     * @dataProvider supportsClassDataProvider
     */
    public function testSupportsClass($class, $actualClass, $expected)
    {
        $this->voter->setClassName($actualClass);

        $this->assertEquals($expected, $this->voter->supportsClass($class));
    }

    /**
     * @return array
     */
    public function supportsClassDataProvider()
    {
        return [
            'supported'     => ['stdClass', 'stdClass', true],
            'not_supported' => ['NotSupportedClass', 'stdClass', false],
        ];
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
            'OroCRM\Bundle\DotmailerBundle\Entity\Repository\ContactRepository'
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
                        ['OroCRM\Bundle\DotmailerBundle\Entity\Contact', $contactRepository],
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
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getItem()
    {
        $item = $this->getMock('OroCRM\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface');
        $marketingList = $this->getMock('OroCRM\Bundle\MarketingListBundle\Entity\MarketingList');

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
