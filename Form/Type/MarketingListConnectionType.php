<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MarketingListConnectionType extends AbstractType
{
    const NAME = 'oro_dotmailer_marketing_list_connection';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $marketingList = $options['marketingList'];

        $builder
            ->add(
                'channel',
                'oro_dotmailer_integration_select',
                [
                    'label'    => 'oro.dotmailer.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'addressBook',
                'oro_dotmailer_address_book_list_select',
                [
                    'label'         => 'oro.dotmailer.addressbook.entity_label',
                    'required'      => true,
                    'channel_field' => 'channel',
                    'marketing_list_id' => $marketingList->getId(),
                    'constraints'   => [new NotBlank()],
                ]
            )
            ->add(
                'isCreateEntities',
                'checkbox',
                [
                    'label'    => 'oro.dotmailer.addressbook.is_create_entities.label',
                    'tooltip'  => 'oro.dotmailer.addressbook.is_create_entities.tooltip',
                    'required' => false
                ]
            );

        $builder->get('addressBook')
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $formEvent) {
                    $form = $formEvent->getForm();
                    $addressBook = $form->getData();
                    if ($addressBook) {
                        $addressBook->setMarketingList(null);
                    }
                }
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $formEvent) use ($marketingList) {
                    $form = $formEvent->getForm();
                    $addressBook = $form->getData();
                    $addressBook->setMarketingList($marketingList);
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['marketingList']);
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
