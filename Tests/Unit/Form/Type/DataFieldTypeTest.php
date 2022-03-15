<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType as EnumSelectTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class DataFieldTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    public function testSubmit()
    {
        $submittedData = [
            'channel' => 1,
            'name' => 'Test Field',
            'owner' => 1,
            'type' => DataField::FIELD_TYPE_STRING,
            'visibility' => DataField::VISIBILITY_PRIVATE,
            'defaultValue' => 'test',
            'notes' => ''
        ];
        $expectedData = $this->getEntity(
            DataFieldStub::class,
            [
                'channel' => $this->getEntity(Channel::class, ['id' => 1]),
                'name' => 'Test Field',
                'type' => DataField::FIELD_TYPE_STRING,
                'visibility' => DataField::VISIBILITY_PRIVATE,
                'defaultValue' => 'test',
                'notes' => ''
            ]
        );

        $form = $this->factory->create(DataFieldType::class);
        $form->submit($submittedData);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $subscriber = $this->getMockBuilder(DataFieldFormSubscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['preSet', 'preSubmit'])
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    new DataFieldType(DataFieldStub::class, $subscriber),
                    IntegrationSelectType::class => new EntityType(
                        ['1' => $this->getEntity(Channel::class, ['id' => 1])],
                        IntegrationSelectType::NAME
                    ),
                    EnumSelectType::class => new EnumSelectTypeStub([
                        // Field "type"
                        new TestEnumValue('String', 'String'),
                        // Field "visibility"
                        new TestEnumValue('Private', 'Private')
                    ])
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            )
        ];
    }
}
