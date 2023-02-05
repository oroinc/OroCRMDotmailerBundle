<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Type\AddressBookType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension\Stub\DynamicFieldsExtensionStub;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AddressBookTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [

                    IntegrationSelectType::class => new EntityTypeStub(['1' => $this->getChannel(1)]),
                    EnumSelectType::class => new EnumSelectTypeStub([
                        new TestEnumValue('Public', 'Public'),
                        new TestEnumValue('Private', 'Private')
                    ])
                ],
                [
                    AddressBookType::class => [
                        new DynamicFieldsExtensionStub([
                            ['visibility', EnumSelectType::class, ['label' => 'base_label']]
                        ])
                    ],
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

    public function testBuildForm()
    {
        $form = $this->factory->create(AddressBookType::class);
        $this->assertSame(AddressBook::class, $form->getConfig()->getOptions()['data_class']);

        $this->assertTrue($form->has('channel'));
        $channelOptions = $form->get('channel')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.integration.label', $channelOptions['label']);
        $this->assertTrue($channelOptions['required']);

        $this->assertTrue($form->has('name'));
        $nameOptions = $form->get('name')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.addressbook.name.label', $nameOptions['label']);
        $this->assertTrue($nameOptions['required']);

        $this->assertTrue($form->has('visibility'));
        $visibilityOptions = $form->get('visibility')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.addressbook.visibility.label', $visibilityOptions['label']);
        $this->assertSame('oro.dotmailer.addressbook.visibility.tooltip', $visibilityOptions['tooltip']);
        $this->assertSame('dm_ab_visibility', $visibilityOptions['enum_code']);
        $this->assertSame([AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION], $visibilityOptions['excluded_values']);
        $this->assertTrue($visibilityOptions['required']);
    }
}
