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
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MarketingListBundle\Form\Type\ContactInformationEntityChoiceType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DataFieldMappingTypeTest extends FormIntegrationTestCase
{
    private DataFieldMappingType $formType;

    protected function setUp(): void
    {
        $subscriber = $this->getMockBuilder(DataFieldMappingFormSubscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['postSet', 'preSubmit'])
            ->getMock();

        $this->formType = new DataFieldMappingType(DataFieldMapping::class, $subscriber);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    IntegrationSelectType::class => new EntityTypeStub([
                        '1' => $this->getChannel(1)
                    ]),
                    ContactInformationEntityChoiceType::class => new EntityTypeStub(['lead' => 'leadClass']),
                    DataFieldSelectType::class => new EntityTypeStub(
                        ['1' => $this->getDataField(1)],
                        ['channel_field' => '']
                    ),
                    new DataFieldMappingConfigType(DataFieldMappingConfig::class),
                    new CollectionType()
                ],
                [
                    FormType::class => [
                        new TooltipFormExtensionStub($this),
                        new FormTypeValidatorExtensionStub()
                    ]
                ]
            )
        ];
    }

    private function getChannel(int $id): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);

        return $channel;
    }

    private function getDataField(int $id): DataField
    {
        $dataField = new DataField();
        ReflectionUtil::setId($dataField, $id);

        return $dataField;
    }

    public function testSubmit()
    {
        $submittedData = [
            'channel' => 1,
            'entity'  => 'lead',
            'syncPriority' => 100,
            'owner' => 1,
            'configs' => [
                ['entityFields' => 'field', 'dataField' => 1, 'isTwoWaySync' => 1]
            ]
        ];

        $expectedEntity = new DataFieldMapping();
        $expectedEntity->setChannel($this->getChannel(1));
        $expectedEntity->setSyncPriority(100);
        $expectedEntity->setEntity('leadClass');
        $config = new DataFieldMappingConfig();
        $config->setEntityFields('field');
        $config->setDataField($this->getDataField(1));
        $config->setIsTwoWaySync(true);
        $expectedEntity->addConfig($config);

        $form = $this->factory->create(DataFieldMappingType::class);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedEntity, $form->getData());
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
