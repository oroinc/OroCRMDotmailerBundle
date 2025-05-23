<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\CampaignBundle\Form\Type\AbstractTransportSettingsType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DotmailerTransportSettingsType extends AbstractTransportSettingsType
{
    const NAME = 'oro_dotmailer_email_transport_settings';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'channel',
                IntegrationSelectType::class,
                [
                    'label' => 'oro.dotmailer.emailcampaign.integration.label',
                    'required' => true
                ]
            );

        parent::buildForm($builder, $options);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\DotmailerBundle\Entity\DotmailerTransportSettings'
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
