<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DataFieldType extends AbstractType
{
    const NAME = 'oro_dotmailer_data_field';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

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
                    'label'    => 'oro.dotmailer.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'name',
                'text',
                [
                    'label' => 'oro.dotmailer.datafield.name.label',
                    'required' => true
                ]
            )
            ->add(
                'type',
                'oro_enum_select',
                [
                    'label' => 'oro.dotmailer.datafield.type.label',
                    'enum_code' => 'dm_df_type',
                    'required' => true,
                ]
            )
            ->add(
                'visibility',
                'oro_enum_select',
                [
                    'label' => 'oro.dotmailer.datafield.visibility.label',
                    'enum_code' => 'dm_df_visibility',
                    'required' => true,
                ]
            )
            ->add(
                'defaultValue',
                'text',
                [
                    'label' => 'oro.dotmailer.datafield.default_value.label',
                    'required' => false
                ]
            )
            ->add(
                'notes',
                'oro_resizeable_rich_text',
                [
                    'label' => 'oro.dotmailer.datafield.notes.label',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'cascade_validation' => true,
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
