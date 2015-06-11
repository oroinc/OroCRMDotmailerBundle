<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IntegrationSettingsType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_transport_setting_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                'text',
                [
                    'label'    => 'orocrm.dotmailer.integration_transport.username.label',
                    'tooltip'  => 'orocrm.dotmailer.form.username.tooltip',
                    'required' => true
                ]
            )
            ->add(
                'password',
                'orocrm_dm_password_type',
                [
                    'label'    => 'orocrm.dotmailer.integration_transport.password.label',
                    'tooltip'  => 'orocrm.dotmailer.form.password.tooltip',
                    'required' => true
                ]
            )->add(
                'check',
                'orocrm_dotmailer_transport_check_button',
                [
                    'label' => 'orocrm.dotmailer.integration.check_connection.label'
                ]
            );

        // edit mode, remove not allowed to update fields
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $formData = $event->getForm()->getData();
                $data = $event->getData();

                if ($formData && $formData->getId() && isset($data['password']) && $data['password'] === '') {
                    $data['password'] = $formData->getPassword();
                    $event->setData($data);
                }
            },
            900
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransport',
                'validation_groups' => function (FormInterface $form) {
                    if ($form->getData() && $form->getData()->getId()) {
                        return ['Default'];
                    } else {
                        return ['Default', 'Create'];
                    }
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
