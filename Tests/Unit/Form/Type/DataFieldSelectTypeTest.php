<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DataFieldSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    CreateOrSelectInlineChannelAwareType::class => new EntityTypeStub()
                ],
                []
            )
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(DataFieldSelectType::class);

        $formOptions = $form->getConfig()->getOptions();

        $this->assertSame('dotmailer_data_fields', $formOptions['autocomplete_alias']);
        $this->assertSame('oro_dotmailer_datafield_grid', $formOptions['grid_name']);
        $this->assertSame(['placeholder'  => 'oro.dotmailer.datafield.select.placeholder'], $formOptions['configs']);
    }

    public function testBuildView()
    {
        $formView = new FormView();
        $formView->parent = new FormView();
        $formView->parent->vars['full_name'] = '';
        $formView->parent->parent = new FormView();
        $fieldNameView = new FormView();
        $fieldNameView->vars['full_name'] = 'full_channel_field_name';
        $formView->parent->parent->children['channel_field_name'] = $fieldNameView;

        $form = $this->createMock(FormInterface::class);

        $formType = new DataFieldSelectType();
        $formType->buildView(
            $formView,
            $form,
            [
                'channel_field' => 'channel_field_name',
                'configs' => [
                    'component' => ''
                ],
                'channel_required' => ''
            ]
        );

        $this->assertArrayHasKey('channel_field_name', $formView->vars);
        $this->assertEquals('full_channel_field_name', $formView->vars['channel_field_name']);

        $this->assertArrayHasKey('component_options', $formView->vars);
        $this->assertArrayHasKey('channel_field_name', $formView->vars['component_options']);
        $this->assertEquals('full_channel_field_name', $formView->vars['component_options']['channel_field_name']);
    }

    public function testGetParent()
    {
        $formType = new DataFieldSelectType();
        $this->assertEquals(
            CreateOrSelectInlineChannelAwareType::class,
            $formType->getParent()
        );
    }
}
