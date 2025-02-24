<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\DotmailerBundle\Tests\Unit\Stub\DataFieldStub;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class DataFieldTypeTest extends FormIntegrationTestCase
{
    public function testSubmit()
    {
        $submittedData = [
            'channel' => 1,
            'name' => 'Test Field',
            'owner' => 1,
            'type' => ExtendHelper::buildEnumOptionId('test_enum_code', DataField::FIELD_TYPE_STRING),
            'visibility' => ExtendHelper::buildEnumOptionId('test_enum_code', DataField::VISIBILITY_PRIVATE),
            'defaultValue' => 'test',
            'notes' => 'note'
        ];
        $expectedData = new DataFieldStub();
        $expectedData->setChannel($this->getChannel(1));
        $expectedData->setName('Test Field');
        $expectedData->setType(new TestEnumValue('test_enum_code', 'test', DataField::FIELD_TYPE_STRING, 0));
        $expectedData->setVisibility(new TestEnumValue('test_enum_code', 'test', DataField::VISIBILITY_PRIVATE, 0));
        $expectedData->setDefaultValue('test');
        $expectedData->setNotes('note');

        $form = $this->factory->create(DataFieldType::class);
        $form->submit($submittedData);

        //        $this->assertTrue($form->isSubmitted());
        //        $this->assertTrue($form->isSynchronized());
        //        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    #[\Override]
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
                    IntegrationSelectType::class => new EntityTypeStub(['1' => $this->getChannel(1)]),
                    EnumSelectType::class => new EnumSelectTypeStub([
                        // Field "type"
                        new TestEnumValue('test_enum_code', 'test', DataField::FIELD_TYPE_STRING, 0),
                        // Field "visibility"
                        new TestEnumValue('test_enum_code', 'test', DataField::VISIBILITY_PRIVATE, 0)
                    ])
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
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
}
