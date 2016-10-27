<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('visibility', 'entity', [
                'label'    => 'oro.dotmailer.addressbook.visibility.label',
                'class'    => ExtendHelper::buildEnumValueClassName('dm_ab_visibility'),
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('v')
                        ->where('v.id IN (:ids)')
                        ->setParameter('ids', [AddressBook::VISIBILITY_PRIVATE, AddressBook::VISIBILITY_PUBLIC]);
                },
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\DotmailerBundle\Entity\AddressBook',
            'validation_groups' => ['AddressBook', 'Default']
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
