<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroCRM\Bundle\CampaignBundle\Form\Type\AbstractTransportSettingsType;

class DotmailerTransportSettingsType extends AbstractTransportSettingsType
{
    const NAME = 'oro_dotmailer_email_transport_settings';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'channel',
                'oro_dotmailer_integration_select',
                [
                    'label' => 'oro.dotmailer.emailcampaign.integration.label',
                    'required' => true
                ]
            );

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\DotmailerBundle\Entity\DotmailerTransportSettings'
            ]
        );
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
