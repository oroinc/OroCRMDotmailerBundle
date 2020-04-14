<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingConfigType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldSelectType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\MarketingListBundle\Form\Type\ContactInformationEntityChoiceType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;

class DataFieldMappingTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var  DataFieldMappingType $type */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $subscriber = $this->createPartialMock(
            'Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber',
            ['postSet', 'preSubmit']
        );
        $this->formType = new DataFieldMappingType(
            'Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping',
            $subscriber
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($this->formType);
        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        return [
            new PreloadedExtension(
                [
                    DataFieldMappingType::class => $this->formType,
                    IntegrationSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1])
                        ],
                        IntegrationSelectType::NAME
                    ),
                    ContactInformationEntityChoiceType::class => new EntityType(
                        [
                            'lead' => 'leadClass'
                        ],
                        ContactInformationEntityChoiceType::NAME
                    ),
                    DataFieldSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity('Oro\Bundle\DotmailerBundle\Entity\DataField', ['id' => 1])
                        ],
                        DataFieldSelectType::NAME,
                        [
                            'channel_field' => ''
                        ]
                    ),
                    DataFieldMappingConfigType::class =>
                        new DataFieldMappingConfigType('Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig'),
                    CollectionType::class => new CollectionType()
                ],
                [
                    FormType::class => [
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
        $form = $this->factory->create(DataFieldMappingType::class, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
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
        $config->setDataField($this->getEntity('Oro\Bundle\DotmailerBundle\Entity\DataField', ['id' => 1]));
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
        $form = $this->factory->create(DataFieldMappingType::class);

        $this->assertSame(
            'Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping',
            $form->getConfig()->getOptions()['data_class']
        );
    }

    public function testFinishView()
    {
        $formView = new FormView();
        $mappingConfigsView = new FormView();
        $formView->children['configs'] = $mappingConfigsView;

        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->formType->finishView($formView, $form, []);

        $this->assertTrue($mappingConfigsView->isRendered());
    }
}
