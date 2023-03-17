<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\DotmailerBundle\Model\CampaignHelper;

class CampaignHelperTest extends \PHPUnit\Framework\TestCase
{
    private CampaignHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CampaignHelper();
    }

    /**
     * @dataProvider codesDataProvider
     */
    public function testGenerateCode(string $name, string $originId, string $expectedCode)
    {
        $this->assertEquals($expectedCode, $this->helper->generateCode($name, $originId));
    }

    public function codesDataProvider(): array
    {
        return [
            [
                'name' => 'Campaign',
                'origin_id' => '123',
                'expected_code' => 'Campaign_123'
            ],
            [
                'name' => 'Very Long Campaign Name',
                'origin_id' => '12352',
                'expected_code' => 'Very_Long_Camp_12352'
            ]
        ];
    }
}
