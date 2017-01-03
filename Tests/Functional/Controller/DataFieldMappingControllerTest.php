<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class DataFieldMappingControllerTest extends AbstractDatagridTestCase
{
    /** @var bool */
    protected $isRealGridRequest = false;

    protected function setUp()
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldMappingData',
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orocrm_dotmailer_datafield_mapping_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return string
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orocrm_dotmailer_datafield_mapping_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $entityClass = 'OroCRM\Bundle\ContactBundle\Entity\Contact';
        $form['orocrm_dotmailer_datafield_mapping_form[entity]'] = $entityClass;
        $form['orocrm_dotmailer_datafield_mapping_form[syncPriority]'] = 100;
        $form['orocrm_dotmailer_datafield_mapping_form[channel]'] =
            $this->getReference('orocrm_dotmailer.channel.third')->getId();
        $mapping = json_encode([
            'mapping' => [
                [
                    'entityFields' => 'firstName',
                    'dataField' => [
                        'value' => $this->getReference('orocrm_dotmailer.datafield.first')->getId(),
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => true
                ]
            ]
        ]);
        $form['orocrm_dotmailer_datafield_mapping_form[config_source]'] = $mapping;
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Data Field Mapping Saved", $crawler->html());
    }

    /**
     * @depends testCreate
     *
     * @return string
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'orocrm_dotmailer_datafield_mapping_grid',
            [
                'orocrm_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'Contact',
                'orocrm_dotmailer_datafield_mapping_grid[_filter][entity][type]' => 1
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $returnValue = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orocrm_dotmailer_datafield_mapping_update', ['id' => $result['id']])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['orocrm_dotmailer_datafield_mapping_form[syncPriority]'] = 200;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Data Field Mapping Saved", $crawler->html());

        return $returnValue;
    }

    /**
     * @param array $returnValue
     * @depends testUpdate
     */
    public function testDelete($returnValue)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_dotmailer_datafield_mapping', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('orocrm_dotmailer_datafield_mapping_update', ['id' => $returnValue['id']])
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
            'Data Fields Mapping grid' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'orocrm_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_mapping_grid[_sort_by][entity][value]' => 'ASC',
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields Mapping grid with filters' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'orocrm_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'Contact',
                        'orocrm_dotmailer_datafield_mapping_grid[_filter][entity][type]' => 1,
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields Mapping grid without result' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'orocrm_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'orocrm_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'non existing entity',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
        ];
    }
}
