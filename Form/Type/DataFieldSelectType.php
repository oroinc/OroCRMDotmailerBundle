<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting Dotmailer data fields.
 *
 * Provides a form field for selecting from available Dotmailer data fields.
 */
class DataFieldSelectType extends CreateOrSelectInlineChannelAwareType
{
    public const NAME = 'oro_dotmailer_datafield_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'dotmailer_data_fields',
                'grid_name'          => 'oro_dotmailer_datafield_grid',
                'configs'            => [
                    'placeholder'  => 'oro.dotmailer.datafield.select.placeholder',
                ]
            ]
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        //take channel field name from the parent form of collection form with data field select
        if (isset($view->parent->parent[$options['channel_field']])) {
            $view->vars['channel_field_name'] =
                $view->parent->parent[$options['channel_field']]->vars['full_name'];
            $view->vars['component_options']['channel_field_name'] = $view->vars['channel_field_name'];
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CreateOrSelectInlineChannelAwareType::class;
    }

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
