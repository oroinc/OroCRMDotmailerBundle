<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DotmailerBundle\Placeholders\ButtonFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class ButtonFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContactInformationFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldsProvider;

    /** @var ButtonFilter */
    private $target;

    protected function setUp(): void
    {
        $this->fieldsProvider = $this->createMock(ContactInformationFieldsProvider::class);

        $this->target = new ButtonFilter($this->fieldsProvider);
    }

    public function testIsApplicable()
    {
        $actual = $this->target->isApplicable(new \stdClass());
        $this->assertFalse($actual);

        $entity = $this->createMock(MarketingList::class);

        $actual = $this->target->isApplicable($entity);
        $this->assertFalse($actual);

        $this->fieldsProvider->expects($this->once())
            ->method('getMarketingListTypedFields')
            ->with($entity, ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL)
            ->willReturn(true);
        $actual = $this->target->isApplicable($entity);
        $this->assertTrue($actual);
    }
}
