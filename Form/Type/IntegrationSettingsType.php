<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\DotmailerBundle\Form\EventListener\IntegrationSettingsSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * DotMailer transport settings form
 */
class IntegrationSettingsType extends AbstractType
{
    const NAME = 'oro_dotmailer_transport_setting_type';

    /**
     * @var IntegrationSettingsSubscriber
     */
    protected $subscriber;

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
                TextType::class,
                [
                    'label'    => 'oro.dotmailer.integration_transport.username.label',
                    'tooltip'  => 'oro.dotmailer.form.username.tooltip',
                    'required' => true,
                    'attr'     => [
                        'class' => 'dm-username',
                    ],
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'label'       => 'oro.dotmailer.integration_transport.password.label',
                    'tooltip'     => 'oro.dotmailer.form.password.tooltip',
                    'required'    => true,
                    'constraints' => [new NotBlank()],
                    'attr'        => [
                        'class' => 'dm-password',
                        'autocomplete' => 'new-password'
                    ],
                ]
            )
            ->add(
                'check',
                DotmailerTransportCheckButtonType::class,
                [
                    'label' => 'oro.dotmailer.integration.check_connection.label'
                ]
            )
            ->add(
                'clientId',
                TextType::class,
                [
                    'label'    => 'oro.dotmailer.integration_transport.client_id.label',
                    'tooltip'  => 'oro.dotmailer.form.client_id.tooltip',
                    'required' => false,
                    'attr'     => [
                        'class' => 'dm-client-id',
                    ],
                ]
            )
            ->add(
                'clientKey',
                PasswordType::class,
                [
                    'label'    => 'oro.dotmailer.integration_transport.client_key.label',
                    'required' => false,
                    'attr'     => [
                        'class' => 'dm-client-key',
                        'autocomplete' => 'new-password'
                    ],
                ]
            )
            ->add(
                'customDomain',
                TextType::class,
                [
                    'label'    => 'oro.dotmailer.integration_transport.custom_domain.label',
                    'tooltip'  => 'oro.dotmailer.form.custom_domain.tooltip',
                    'required' => false,
                    'attr'     => [
                        'class' => 'dm-custom-domain',
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport',
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
