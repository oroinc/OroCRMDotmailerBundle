<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
                'password',
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
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => 'OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransport']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
