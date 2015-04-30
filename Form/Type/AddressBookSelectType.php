<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AddressBookSelectType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_address_book_list_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'dotmailer_address_books',
                'grid_name'          => 'orocrm_dotmailer_address_books_grid',
                'configs'            => [
                    'placeholder' => 'orocrm.dotmailer.addressbook.select.placeholder'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_entity_create_or_select_inline_channel_aware';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
