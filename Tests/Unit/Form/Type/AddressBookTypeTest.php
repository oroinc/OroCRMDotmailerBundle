<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Type\AddressBookType;
use Oro\Bundle\DotmailerBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension\Stub\DynamicFieldsExtensionStub;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
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
                            new StubEnumValue('Public', 'Public'),
                            new StubEnumValue('Private', 'Private')
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
        $this->assertArraySubset(['data_class' => AddressBook::class], $form->getConfig()->getOptions());

        $this->assertTrue($form->has('channel'));
        $this->assertArraySubset(
            [
                'label' => 'oro.dotmailer.integration.label',
                'required' => true
            ],
            $form->get('channel')->getConfig()->getOptions()
        );

        $this->assertTrue($form->has('name'));
        $this->assertArraySubset(
            [
                'label' => 'oro.dotmailer.addressbook.name.label',
                'required' => true
            ],
            $form->get('name')->getConfig()->getOptions()
        );

        $this->assertTrue($form->has('visibility'));
        $this->assertArraySubset(
            [
                'label' => 'oro.dotmailer.addressbook.visibility.label',
                'tooltip' => 'oro.dotmailer.addressbook.visibility.tooltip',
                'enum_code' => 'dm_ab_visibility',
                'excluded_values' => [AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION],
                'required' => true
            ],
            $form->get('visibility')->getConfig()->getOptions()
        );
    }
}
