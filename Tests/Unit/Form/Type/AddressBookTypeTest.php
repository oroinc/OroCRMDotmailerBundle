<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Type\AddressBookType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension\Stub\DynamicFieldsExtensionStub;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType as EnumSelectTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AddressBookTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [

                    IntegrationSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity(Channel::class, ['id' => 1])
                        ],
                        IntegrationSelectType::NAME
                    ),
                    EnumSelectType::class => new EnumSelectTypeStub(
                        [
                            new TestEnumValue('Public', 'Public'),
                            new TestEnumValue('Private', 'Private')
                        ]
                    )
                ],
                [
                    AddressBookType::class => [
                        new DynamicFieldsExtensionStub([
                            [
                                'visibility',
                                EnumSelectType::class,
                                ['label' => 'base_label']
                            ]
                        ])
                    ],
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                        new FormTypeValidatorExtensionStub()
                    ]
                ]
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(AddressBookType::class);
        $this->assertSame(AddressBook::class, $form->getConfig()->getOptions()['data_class']);

        $this->assertTrue($form->has('channel'));
        $channelOptions = $form->get('channel')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.integration.label', $channelOptions['label']);
        $this->assertSame(true, $channelOptions['required']);

        $this->assertTrue($form->has('name'));
        $nameOptions = $form->get('name')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.addressbook.name.label', $nameOptions['label']);
        $this->assertSame(true, $nameOptions['required']);

        $this->assertTrue($form->has('visibility'));
        $visibilityOptions = $form->get('visibility')->getConfig()->getOptions();
        $this->assertSame('oro.dotmailer.addressbook.visibility.label', $visibilityOptions['label']);
        $this->assertSame('oro.dotmailer.addressbook.visibility.tooltip', $visibilityOptions['tooltip']);
        $this->assertSame('dm_ab_visibility', $visibilityOptions['enum_code']);
        $this->assertSame([AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION], $visibilityOptions['excluded_values']);
        $this->assertSame(true, $visibilityOptions['required']);
    }
}
