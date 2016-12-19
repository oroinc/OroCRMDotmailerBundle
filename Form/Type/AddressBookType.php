<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class AddressBookType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('channel', 'orocrm_dotmailer_integration_select', [
                'label'    => 'orocrm.dotmailer.integration.label',
                'required' => true
            ])
            ->add('name', 'text', [
                'label'    => 'orocrm.dotmailer.addressbook.name.label',
                'required' => true
            ])
            ->add('visibility', 'oro_enum_select', [
                'label'           => 'orocrm.dotmailer.addressbook.visibility.label',
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
            'data_class' => 'OroCRM\Bundle\DotmailerBundle\Entity\AddressBook'
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
        return 'orocrm_dotmailer_address_book_form';
    }
}
