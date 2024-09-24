<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IntegrationConnectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('channel', IntegrationSelectType::class, [
                'label'       => 'oro.dotmailer.integration.label',
                'placeholder' => 'oro.dotmailer.integration.select.placeholder',
                'required'    => false
            ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_dotmailer_integration_connection';
    }
}
