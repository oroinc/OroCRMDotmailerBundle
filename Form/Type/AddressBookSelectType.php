<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressBookSelectType extends CreateOrSelectInlineChannelAwareType
{
    const NAME = 'oro_dotmailer_address_book_list_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['marketing_list_id']);
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'dotmailer_address_books',
                'create_form_route'  => 'oro_dotmailer_address_book_create',
                'grid_name'          => 'oro_dotmailer_address_books_grid',
                'configs'            => [
                    'placeholder'  => 'oro.dotmailer.addressbook.select.placeholder',
                ]
            ]
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['configs']['component'] .= '-address-book';
        $view->vars['marketing_list_id'] = isset($options['marketing_list_id']) ? $options['marketing_list_id'] : null;
        $view->vars['component_options']['marketing_list_id'] = $view->vars['marketing_list_id'];
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
