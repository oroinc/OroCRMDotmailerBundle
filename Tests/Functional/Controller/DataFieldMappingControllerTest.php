<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Controller;

use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldMappingData;

class DataFieldMappingControllerTest extends AbstractDatagridTestCase
{
    protected bool $isRealGridRequest = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadDataFieldMappingData::class]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_dotmailer_datafield_mapping_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_dotmailer_datafield_mapping_create'));
        $form = $crawler->selectButton('Save')->form();
        $entityClass = Contact::class;
        $form['oro_dotmailer_datafield_mapping_form[entity]'] = $entityClass;
        $form['oro_dotmailer_datafield_mapping_form[syncPriority]'] = 100;
        $form['oro_dotmailer_datafield_mapping_form[channel]'] =
            $this->getReference('oro_dotmailer.channel.third')->getId();
        $mapping = json_encode([
            'mapping' => [
                [
                    'entityFields' => 'firstName',
                    'dataField' => [
                        'value' => $this->getReference('oro_dotmailer.datafield.first')->getId(),
                        'name' => 'dataFieldName'
                    ],
                    'isTwoWaySync' => true
                ]
            ]
        ], JSON_THROW_ON_ERROR);
        $form['oro_dotmailer_datafield_mapping_form[config_source]'] = $mapping;
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Data Field Mapping Saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): array
    {
        $response = $this->client->requestGrid(
            'oro_dotmailer_datafield_mapping_grid',
            [
                'oro_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'Contact',
                'oro_dotmailer_datafield_mapping_grid[_filter][entity][type]' => 1
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $returnValue = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dotmailer_datafield_mapping_update', ['id' => $result['id']])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_dotmailer_datafield_mapping_form[syncPriority]'] = 200;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Data Field Mapping Saved', $crawler->html());

        return $returnValue;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(array $returnValue)
    {
        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_dotmailer_datafield_mapping', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('oro_dotmailer_datafield_mapping_update', ['id' => $returnValue['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * {@inheritdoc}
     */
    public function gridProvider(): array
    {
        return [
            'Data Fields Mapping grid' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_mapping_grid[_sort_by][entity][value]' => 'ASC',
                    ],
                    'assert'              => [
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields Mapping grid with filters' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'Contact',
                        'oro_dotmailer_datafield_mapping_grid[_filter][entity][type]' => 1,
                    ],
                    'assert'              => [
                    ],
                    'expectedResultCount' => 2
                ],
            ],
            'Data Fields Mapping grid without result' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'oro_dotmailer_datafield_mapping_grid'
                    ],
                    'gridFilters'         => [
                        'oro_dotmailer_datafield_mapping_grid[_filter][entity][value]' => 'non existing entity',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
        ];
    }
}
