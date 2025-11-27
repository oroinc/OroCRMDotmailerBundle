<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider;

use Oro\Bundle\DotmailerBundle\Provider\CsvStringReader;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class CsvStringReaderTest extends TestCase
{
    private function getTempFilePath(CsvStringReader $reader): string
    {
        /** @var \SplFileInfo $tempFileInfo */
        $tempFileInfo = ReflectionUtil::getPropertyValue($reader, 'fileInfo');

        return $tempFileInfo->getRealPath();
    }

    public function testReadEmpty(): void
    {
        $reader = new CsvStringReader('field1,field2');
        $tempFilePath = $this->getTempFilePath($reader);
        try {
            self::assertNull($reader->read());
        } finally {
            $reader = null;
            self::assertFileDoesNotExist($tempFilePath);
        }
    }

    public function testRead(): void
    {
        $reader = new CsvStringReader("field1,field2\nval1,val2");
        $tempFilePath = $this->getTempFilePath($reader);
        try {
            self::assertEquals(['field1' => 'val1', 'field2' => 'val2'], $reader->read());
        } finally {
            $reader = null;
            self::assertFileDoesNotExist($tempFilePath);
        }
    }
}
