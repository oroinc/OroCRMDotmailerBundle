<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MarketingListConnectionType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_marketing_list_connection';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $marketingList = $options['marketingList'];

        $builder
            ->add(
                'channel',
                'orocrm_dotmailer_integration_select',
                [
                    'label'    => 'orocrm.dotmailer.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'addressBook',
                'orocrm_dotmailer_address_book_list_select',
                [
                    'label'         => 'orocrm.dotmailer.addressbook.entity_label',
                    'required'      => true,
                    'channel_field' => 'channel',
                    'marketing_list_id' => $marketingList->getId(),
                    'constraints'   => [new NotBlank()],
                ]
            );

        $builder->get('addressBook')
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
        return self::NAME;
    }
}
