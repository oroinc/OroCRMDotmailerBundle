<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroCRM\Bundle\DotmailerBundle\Form\EventListener\IntegrationSettingsSubscriber;

class IntegrationSettingsType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_transport_setting_type';

    /**
     * @var IntegrationSettingsSubscriber
     */
    protected $subscriber;

    /**
     * @param IntegrationSettingsSubscriber $subscriber
     */
    public function __construct(IntegrationSettingsSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder
            ->add(
                'username',
                'text',
                [
                    'label'    => 'orocrm.dotmailer.integration_transport.username.label',
                    'tooltip'  => 'orocrm.dotmailer.form.username.tooltip',
                    'required' => true,
                    'attr'     => [
                        'class' => 'dm-username',
                    ],
                ]
            )
            ->add(
                'password',
                'password',
                [
                    'label'       => 'orocrm.dotmailer.integration_transport.password.label',
                    'tooltip'     => 'orocrm.dotmailer.form.password.tooltip',
                    'required'    => true,
                    'constraints' => [new NotBlank()],
                    'attr'        => [
                        'class' => 'dm-password',
                    ],
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
