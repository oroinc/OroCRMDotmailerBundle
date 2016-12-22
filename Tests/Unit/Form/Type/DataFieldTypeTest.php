<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type\Stub\EnumSelectType;

class DataFieldTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var  DataFieldType $type */
    protected $formType;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = $this->createPartialMock(
            'Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber',
            ['preSet', 'preSubmit']
        );

        $this->formType = new DataFieldType(
            'Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub',
            $this->subscriber
        );
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

    public function testGetName()
    {
        $this->assertEquals(DataFieldType::NAME, $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_dotmailer_integration_select' => new EntityType(
                        [
                            '1' => $this->getEntity('Oro\Bundle\IntegrationBundle\Entity\Channel', ['id' => 1])
                        ],
                        'oro_dotmailer_integration_select'
                    ),
                    'oro_enum_select' => new EnumSelectType(),
                ],
                []
            )
        ];
    }
}
