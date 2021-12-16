<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Controller;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadDataFieldData;

class DataFieldControllerTest extends AbstractDatagridTestCase
{
    protected bool $isRealGridRequest = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadDataFieldData::class]);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_dotmailer_datafield_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView(): array
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
        self::assertStringContainsString(
            "{$returnValue['name']} - Data Fields - dotdigital - Marketing",
            $crawler->html()
        );

        return $returnValue;
    }

    /**
     * @depends testView
     */
    public function testInfo(array $returnValue)
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
        self::assertStringContainsString($returnValue['name'], $crawler->html());
    }

    /**
     * {@inheritdoc}
     */
    public function gridProvider(): array
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
                    'expectedResultCount' => 5
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

    public function testCreateAction(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_dotmailer_datafield_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_dotmailer_data_field_form[channel]'] = $this->getReference('oro_dotmailer.channel.first')->getId();
        $form['oro_dotmailer_data_field_form[name]'] = 'test_name';
        $form['oro_dotmailer_data_field_form[type]'] = 'String';
        $form['oro_dotmailer_data_field_form[visibility]'] = 'Private';
        $form['oro_dotmailer_data_field_form[defaultValue]'] = 'test';
        $form['oro_dotmailer_data_field_form[notes]'] = 'test note';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Data Field Saved', $crawler->html());
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(DataField::class);
        /** @var DataField $dataField */
        $dataField = $em->getRepository(DataField::class)->findOneBy(['name' => 'test_name']);
        $this->assertNotNull($dataField);
        $this->assertNotEmpty($dataField->getOwner());
    }
}
