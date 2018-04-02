<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataFieldType extends AbstractType
{
    const NAME = 'oro_dotmailer_data_field';

    /** @var string */
    protected $dataClass;

    /** @var DataFieldFormSubscriber */
    protected $subscriber;

    /**
     * @param string $dataClass
     * @param DataFieldFormSubscriber $subscriber
     */
    public function __construct($dataClass, DataFieldFormSubscriber $subscriber)
    {
        $this->dataClass = $dataClass;
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
                    'tooltip' => 'oro.dotmailer.datafield.type.tooltip',
                    'enum_code' => 'dm_df_type',
                    'required' => true,
                ]
            )
            ->add(
                'visibility',
                'oro_enum_select',
                [
                    'label' => 'oro.dotmailer.datafield.visibility.label',
                    'tooltip' => 'oro.dotmailer.datafield.visibility.tooltip',
                    'enum_code' => 'dm_df_visibility',
                    'required' => true,
                ]
            )
            ->add(
                'defaultValue',
                'text',
                [
                    'label' => 'oro.dotmailer.datafield.default_value.label',
                    'tooltip' => 'oro.dotmailer.datafield.default_value.tooltip',
                    'required' => false,
                ]
            )
            ->add(
                'notes',
                'textarea',
                [
                    'label' => 'oro.dotmailer.datafield.notes.label',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
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
