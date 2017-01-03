<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use OroCRM\Bundle\DotmailerBundle\Form\Type\DataFieldMappingType;
use OroCRM\Bundle\DotmailerBundle\Form\Type\DataFieldMappingConfigType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;

class DataFieldMappingTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var  DataFieldMappingType $type */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $subscriber = $this->getMock(
            'OroCRM\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber',
            ['postSet', 'preSubmit']
        );
        $this->formType = new DataFieldMappingType(
            'OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping',
            $subscriber
        );
    }

    public function tearDown()
    {
        unset($this->formType);
        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        return [
            new PreloadedExtension(
                [
                    'orocrm_dotmailer_integration_select' => new EntityType(
                        [
                            '1' => $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1])
                        ],
                        'orocrm_dotmailer_integration_select'
                    ),
                    'orocrm_marketing_list_contact_information_entity_choice' => new EntityType(
                        [
                            'lead' => 'leadClass'
                        ],
                        'orocrm_marketing_list_contact_information_entity_choice'
                    ),
                    'orocrm_dotmailer_datafield_select' => new EntityType(
                        [
                            '1' => $this->getEntity('OroCRM\Bundle\DotmailerBundle\Entity\DataField', ['id' => 1])
                        ],
                        'orocrm_dotmailer_datafield_select',
                        [
                            'channel_field' => ''
                        ]
                    ),
                    'orocrm_dotmailer_datafield_mapping_config' =>
                        new DataFieldMappingConfigType('OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig'),
                    'oro_collection' => new CollectionType()
                ],
                [
                    'form' => [
                        new TooltipFormExtension($configProvider, $translator),
                        new FormTypeValidatorExtensionStub()
                    ]
                ]
            )
        ];
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $expectedEntity = new DataFieldMapping();
        $expectedEntity->setChannel($this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]));
        $expectedEntity->setSyncPriority(100);
        $expectedEntity->setEntity('leadClass');
        $config = new DataFieldMappingConfig();
        $config->setEntityFields('field');
        $config->setDataField($this->getEntity('OroCRM\Bundle\DotmailerBundle\Entity\DataField', ['id' => 1]));
        $config->setIsTwoWaySync(true);
        $expectedEntity->addConfig($config);

        return [
            'datafield_valid' => [
                'isValid'       => true,
                'defaultData'   => null,
                'submittedData' => [
                    'channel' => 1,
                    'entity'  => 'lead',
                    'syncPriority' => 100,
                    'owner' => 1,
                    'configs' => [
                        [
                            'entityFields' => 'field',
                            'dataField' => 1,
                            'isTwoWaySync' => 1
                        ]
                    ]
                ],
                'expectedData'  => $expectedEntity
            ]
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $expectedOptions = [
            'data_class' => 'OroCRM\Bundle\DotmailerBundle\Entity\DataFieldMapping',
            'cascade_validation' => true
        ];

        $formOptions = $form->getConfig()->getOptions();

        $this->assertArraySubset($expectedOptions, $formOptions);
    }

    public function testFinishView()
    {
        $formView = new FormView();
        $mappingConfigsView = new FormView();
        $formView->children['configs'] = $mappingConfigsView;

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formType->finishView($formView, $form, []);

        $this->assertTrue($mappingConfigsView->isRendered());
    }

    public function testGetName()
    {
        $this->assertEquals(DataFieldMappingType::NAME, $this->formType->getName());
    }
}
