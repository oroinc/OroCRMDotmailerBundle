<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DotmailerBundle\Placeholders\ButtonFilter;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class ButtonFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ButtonFilter
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    protected function setUp(): void
    {
        $this->fieldsProvider = $this->getMockBuilder(
            'Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ButtonFilter($this->fieldsProvider);
    }

    public function testIsApplicable()
    {
        $actual = $this->target->isApplicable(new \StdClass());
        $this->assertFalse($actual);

        $entity = $this->createMock('Oro\Bundle\MarketingListBundle\Entity\MarketingList');

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
