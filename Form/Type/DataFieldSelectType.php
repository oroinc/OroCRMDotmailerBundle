<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataFieldSelectType extends CreateOrSelectInlineChannelAwareType
{
    const NAME = 'oro_dotmailer_datafield_select';

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CreateOrSelectInlineChannelAwareType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
