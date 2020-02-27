<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Address book form type.
 */
class AddressBookType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('channel', IntegrationSelectType::class, [
                'label' => 'oro.dotmailer.integration.label',
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'oro.dotmailer.addressbook.name.label',
                'required' => true
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            FormUtils::replaceField(
                $form,
                'visibility',
                [
                    'label' => 'oro.dotmailer.addressbook.visibility.label',
                    'tooltip' => 'oro.dotmailer.addressbook.visibility.tooltip',
                    'enum_code' => 'dm_ab_visibility',
                    'excluded_values' => [AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION],
                    'required' => true,
                    'constraints' => [new Assert\NotNull()]
                ],
                ['constraints']
            );
        }, -500);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AddressBook::class
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
