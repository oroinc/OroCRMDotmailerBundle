<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                IntegrationSelectType::class,
                [
                    'label'    => 'oro.dotmailer.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'addressBook',
                AddressBookSelectType::class,
                [
                    'label'         => 'oro.dotmailer.addressbook.entity_label',
                    'required'      => true,
                    'channel_field' => 'channel',
                    'marketing_list_id' => $marketingList->getId(),
                    'constraints'   => [new NotBlank()],
                ]
            )
            ->add(
                'createEntities',
                CheckboxType::class,
                [
                    'label'    => 'oro.dotmailer.addressbook.create_entities.label',
                    'tooltip'  => 'oro.dotmailer.addressbook.create_entities.tooltip',
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
    public function configureOptions(OptionsResolver $resolver)
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
