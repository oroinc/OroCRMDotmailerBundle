<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\ImportExport\DataConverter\DataFieldDataConverter;

class DataFieldDataConverterTest extends \PHPUnit\Framework\TestCase
{
    private DataFieldDataConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new DataFieldDataConverter();
    }

    public function testConvertToImportFormatDefaultValueArray()
    {
        $importedRecord = ['type' => DataField::FIELD_TYPE_STRING];
        $importedRecord['defaultvalue'] = ['value' => 'Test Default Value'];
        $result = $this->converter->convertToImportFormat($importedRecord);
        $this->assertArrayHasKey('defaultValue', $result);
        $this->assertEquals($result['defaultValue'], 'Test Default Value');
    }

    public function testConvertToImportFormatDefaultValueNullString()
    {
        $importedRecord = ['type' => DataField::FIELD_TYPE_STRING];
        $importedRecord['defaultvalue'] = ['value' => 'null'];
        $result = $this->converter->convertToImportFormat($importedRecord);
        $this->assertArrayNotHasKey('defaultValue', $result);
    }

    public function testConvertToImportFormatDefaultValueBooleanFalse()
    {
        $importedRecord = ['type' => DataField::FIELD_TYPE_BOOLEAN];
        $importedRecord['defaultvalue'] = ['value' => false];
        $result = $this->converter->convertToImportFormat($importedRecord);
        $this->assertArrayHasKey('defaultValue', $result);
        $this->assertEquals($result['defaultValue'], DataField::DEFAULT_BOOLEAN_NO);
    }

    public function testConvertToImportFormatDefaultValueBooleanTrue()
    {
        $importedRecord = ['type' => DataField::FIELD_TYPE_BOOLEAN];
        $importedRecord['defaultvalue'] = ['value' => true];
        $result = $this->converter->convertToImportFormat($importedRecord);
        $this->assertArrayHasKey('defaultValue', $result);
        $this->assertEquals($result['defaultValue'], DataField::DEFAULT_BOOLEAN_YES);
    }
}
