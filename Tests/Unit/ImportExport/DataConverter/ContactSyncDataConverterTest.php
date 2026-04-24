<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\ImportExport\DataConverter\ContactSyncDataConverter;
use Oro\Bundle\DotmailerBundle\Provider\CacheProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class ContactSyncDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var ContactSyncDataConverter */
    private $contactSyncDataConverter;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->contactSyncDataConverter = new ContactSyncDataConverter();
        $this->contactSyncDataConverter->setCacheProvider($this->cacheProvider);
        $this->contactSyncDataConverter->setImportExportContext($this->context);
    }

    public function testConvertToImportFormatEmailTurnedToLowercase()
    {
        $this->context->expects($this->once())
            ->method('hasOption')
            ->with('channel')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('getOption')
            ->with('channel')
            ->willReturn(1);

        $this->cacheProvider->expects($this->once())
            ->method('getCachedItem')
            ->with(ContactSyncDataConverter::CACHED_DATAFIELDS, 1)
            ->willReturn(['DATA_FIELD' => 1]);

        $result = $this->contactSyncDataConverter->convertToImportFormat(['email' => 'FooBar@gmail.com']);
        $this->assertSame(
            ['email' => 'foobar@gmail.com', 'dataFields' => []],
            $result
        );
    }
}
