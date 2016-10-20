<?php

namespace OroCRM\Bundle\SalesBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\InputFormField;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class DataFieldControllerTest extends AbstractDatagridTestCase
{
    /** @var bool */
    protected $isRealGridRequest = false;

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData',
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orocrm_dotmailer_datafield_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return string
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orocrm_dotmailer_datafield_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $name = 'name' . $this->generateRandomString();
        $form['orocrm_dotmailer_data_field[name]'] = $name;
        $form['orocrm_dotmailer_data_field[type]'] = DataField::FIELD_TYPE_STRING;
        $form['orocrm_dotmailer_data_field[visibility]'] = DataField::VISIBILITY_PRIVATE;
        $form['orocrm_dotmailer_data_field[defaultValue]'] = 'test value';
        $form['orocrm_dotmailer_data_field[channel]'] = $this->getReference('orocrm_dotmailer.channel.first')->getId();


        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Data Field Saved", $crawler->html());

        return $name;
    }

    /**
     * @param string $name
     * @depends testCreate
     *
     * @return string
     */
    public function testView($name)
    {
        $response = $this->client->requestGrid(
            'orocrm_dotmailer_datafield_grid',
            ['orocrm_dotmailer_datafield_grid[_filter][name][value]' => $name]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $returnValue = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_dotmailer_datafield_view', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("{$returnValue['name']} - Data Fields - Dotmailer - Marketing", $crawler->html());

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
                'orocrm_dotmailer_datafield_info',
                ['id' => $returnValue['id'], '_widgetContainer' => 'block']
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($returnValue['name'], $crawler->html());
    }

    /**
     * @param array $returnValue
     * @depends testView
     */
    public function testDelete($returnValue)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_dotmailer_datafield', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('orocrm_dotmailer_datafield_view', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
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
                        'gridName' => 'orocrm_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_grid[_sort_by][name][value]' => 'ASC',
                    ],
                    'assert'              => [
                        'channelName' => 'first channel',
                        'name'        => 'FIRSTNAME',
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields grid with filters'   => [
                [
                    'gridParameters'      => [
                        'gridName' => 'orocrm_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_grid[_filter][name][value]' => 'FIRSTNAME',
                    ],
                    'assert'              => [
                        'channelName' => 'first channel',
                        'name'        => 'FIRSTNAME',
                    ],
                    'expectedResultCount' => 1
                ],
            ],
            'Data Fields grid without result' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'orocrm_dotmailer_datafield_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_grid[_filter][name][value]' => 'non existing name',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
        ];
    }
}
