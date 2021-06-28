<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMappingConfig;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingConfigType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldSelectType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Form\Type\ContactInformationEntityChoiceType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DataFieldMappingTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var DataFieldMappingType */
    private $formType;

    protected function setUp(): void
    {
        $subscriber = $this->getMockBuilder(DataFieldMappingFormSubscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['postSet', 'preSubmit'])
            ->getMock();
        $this->formType = new DataFieldMappingType(
            DataFieldMapping::class,
            $subscriber
        );
        parent::setUp();
    }

    protected function getExtensions()
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    DataFieldMappingType::class => $this->formType,
                    IntegrationSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity(Channel::class, ['id' => 1])
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
                            '1' => $this->getEntity(DataField::class, ['id' => 1])
                        ],
                        DataFieldSelectType::NAME,
                        [
                            'channel_field' => ''
                        ]
                    ),
                    DataFieldMappingConfigType::class =>
                        new DataFieldMappingConfigType(DataFieldMappingConfig::class),
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
     * @dataProvider submitProvider
     */
    public function testSubmit(bool $isValid, $defaultData, array $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create(DataFieldMappingType::class, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        $expectedEntity = new DataFieldMapping();
        $expectedEntity->setChannel($this->getEntity(Channel::class, ['id' => 1]));
        $expectedEntity->setSyncPriority(100);
        $expectedEntity->setEntity('leadClass');
        $config = new DataFieldMappingConfig();
        $config->setEntityFields('field');
        $config->setDataField($this->getEntity(DataField::class, ['id' => 1]));
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
            DataFieldMapping::class,
            $form->getConfig()->getOptions()['data_class']
        );
    }

    public function testFinishView()
    {
        $formView = new FormView();
        $mappingConfigsView = new FormView();
        $formView->children['configs'] = $mappingConfigsView;

        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView($formView, $form, []);

        $this->assertTrue($mappingConfigsView->isRendered());
    }
}
