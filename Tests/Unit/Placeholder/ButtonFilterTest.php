<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use OroCRM\Bundle\DotmailerBundle\Placeholders\ButtonFilter;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class ButtonFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ButtonFilter
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMockBuilder(
            'OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ButtonFilter($this->fieldsProvider);
    }

    public function testIsApplicable()
    {
        $actual = $this->target->isApplicable(new \StdClass());
        $this->assertFalse($actual);

        $entity = $this->getMock('OroCRM\Bundle\MarketingListBundle\Entity\MarketingList');

        $actual = $this->target->isApplicable($entity);
        $this->assertFalse($actual);

        $this->fieldsProvider
            ->expects($this->once())
            ->method('getMarketingListTypedFields')
            ->with($entity, ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL)
            ->will($this->returnValue(true));
        $actual = $this->target->isApplicable($entity);
        $this->assertTrue($actual);
    }
}
