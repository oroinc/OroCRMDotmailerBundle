<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DotmailerTransportCheckButtonType extends ButtonType
{
    const NAME = 'oro_dotmailer_transport_check_button';

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
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['attr' => ['class' => 'btn btn-primary']]);
    }
}
