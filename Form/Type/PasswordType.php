<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PasswordType extends AbstractType
{
    const NAME = 'orocrm_dm_password_type';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getParent()->getData() && $form->getParent()->getData()->getId()) {
            $view->vars['required'] = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
