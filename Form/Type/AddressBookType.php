<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

class AddressBookType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('channel', 'oro_dotmailer_integration_select', [
                'label'    => 'oro.dotmailer.integration.label',
                'required' => true
            ])
            ->add('name', 'text', [
                'label'    => 'oro.dotmailer.addressbook.name.label',
                'required' => true
            ])
            ->add('visibility', 'oro_enum_select', [
                'label'           => 'oro.dotmailer.addressbook.name.label',
                'enum_code'       => 'dm_ab_visibility',
                'excluded_values' => [AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION],
                'required'        => true,
                'constraints'     => [new Assert\NotNull()]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\DotmailerBundle\Entity\AddressBook'
        ]);
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
        return 'oro_dotmailer_address_book_form';
    }
}
