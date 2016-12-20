<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldSelectType;

class DataFieldSelectTypeTest extends FormIntegrationTestCase
{
    /** @var  DataFieldSelectType $type */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new DataFieldSelectType();
    }

    public function tearDown()
    {
        unset($this->formType);
        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([], 'oro_entity_create_or_select_inline_channel_aware');

        return [
            new PreloadedExtension(
                [
                    'oro_entity_create_or_select_inline_channel_aware' => $entityType
                ],
                []
            )
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $expectedOptions = [
            'autocomplete_alias' => 'dotmailer_data_fields',
            'grid_name'          => 'oro_dotmailer_datafield_grid',
            'configs'            => [
                'placeholder'  => 'oro.dotmailer.datafield.select.placeholder',
            ]
        ];

        $formOptions = $form->getConfig()->getOptions();

        $this->assertArraySubset($expectedOptions, $formOptions);
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

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formType->buildView(
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

    public function testGetName()
    {
        $this->assertEquals(DataFieldSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'oro_entity_create_or_select_inline_channel_aware',
            $this->formType->getParent()
        );
    }
}
