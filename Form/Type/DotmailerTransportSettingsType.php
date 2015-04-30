<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroCRM\Bundle\CampaignBundle\Form\Type\AbstractTransportSettingsType;

class DotmailerTransportSettingsType extends AbstractTransportSettingsType
{
    const NAME = 'orocrm_dotmailer_email_transport_settings';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'channel',
                'orocrm_dotmailer_integration_select',
                [
                    'label' => 'orocrm.dotmailer.emailcampaign.integration.label',
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
                'data_class' => 'OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransportSettings'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
