<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type\Stub\EnumSelectType as EnumSelectTypeStub;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class DataFieldTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var  DataFieldType $type */
    protected $formType;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->subscriber = $this->createPartialMock(
            'Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber',
            ['preSet', 'preSubmit']
        );

        $this->formType = new DataFieldType(
            'Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub',
            $this->subscriber
        );
        parent::setUp();
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
        $form = $this->factory->create(DataFieldType::class, $defaultData, $options);
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
        $expectedEntity = $this->getEntity(
            'Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub',
            [
                'channel' => $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1]),
                'name' => 'Test Field',
                'type' => DataField::FIELD_TYPE_STRING,
                'visibility' => DataField::VISIBILITY_PRIVATE,
                'defaultValue' => 'test',
                'notes' => ''
            ]
        );
        return [
            'datafield_valid' => [
                'isValid'       => true,
                'defaultData'   => null,
                'submittedData' => [
                    'channel' => 1,
                    'name' => 'Test Field',
                    'owner' => 1,
                    'type' => DataField::FIELD_TYPE_STRING,
                    'visibility' => DataField::VISIBILITY_PRIVATE,
                    'defaultValue' => 'test',
                    'notes' => ''
                ],
                'expectedData'  => $expectedEntity
            ]
        ];
    }

    /**
     * {@inheritdoc}
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
                    DataFieldType::class => $this->formType,
                    IntegrationSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1])
                        ],
                        IntegrationSelectType::NAME
                    ),
                    EnumSelectType::class => new EnumSelectTypeStub(),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                    ]
                ]
            )
        ];
    }
}
