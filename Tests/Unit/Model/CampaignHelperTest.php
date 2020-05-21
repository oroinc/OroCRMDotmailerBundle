<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Model;

use Oro\Bundle\DotmailerBundle\Model\CampaignHelper;

class CampaignHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CampaignHelper
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->helper = new CampaignHelper();
    }

    /**
     * @dataProvider codesDataProvider
     * @param string $name
     * @param string $originId
     * @param string $expectedCode
     */
    public function testGenerateCode($name, $originId, $expectedCode)
    {
        $this->assertEquals($expectedCode, $this->helper->generateCode($name, $originId));
    }

    /**
     * @return array
     */
    public function codesDataProvider()
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
