<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Controller;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;

class DataFieldControllerTest extends AbstractDatagridTestCase
{
    /** @var bool */
    protected $isRealGridRequest = false;

    protected function setUp()
    {
        parent::setUp();

        //@todo: remove after CRM-7961 is resolved
        $this->markTestSkipped('skip until CRM-7961 is resolved');

        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData',
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_dotmailer_datafield_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return string
     */
    public function testView()
    {
        $name = $this->getReference('oro_dotmailer.datafield.first')->getName();
        $response = $this->client->requestGrid(
            'oro_dotmailer_datafield_grid',
            ['oro_dotmailer_datafield_grid[_filter][name][value]' => $name]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $returnValue = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dotmailer_datafield_view', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("{$returnValue['name']} - Data Fields - dotmailer - Marketing", $crawler->html());

        return $returnValue;
    }

    /**
     * @param array $returnValue
     * @depends testView
     *
     * @return string
     */
    public function testInfo($returnValue)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_dotmailer_datafield_info',
                ['id' => $returnValue['id'], '_widgetContainer' => 'block']
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($returnValue['name'], $crawler->html());
    }

    /**
     * @return array
     */
    public function gridProvider()
    {
        return [
            'Data Fields grid'                => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_grid[_sort_by][name][value]' => 'ASC',
                    ],
                    'assert'              => [
                        'channelName' => 'first channel',
                        'name'        => 'FIRSTNAME',
                    ],
                    'expectedResultCount' => 4
                ],
            ],
            'Data Fields grid with filters'   => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_grid[_filter][name][value]' => 'FIRSTNAME',
                    ],
                    'assert'              => [
                        'channelName' => 'first channel',
                        'name'        => 'FIRSTNAME',
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields grid without result' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_grid[_filter][name][value]' => 'non existing name',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
        ];
    }
}
