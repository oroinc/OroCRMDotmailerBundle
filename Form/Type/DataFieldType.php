<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroCRM\Bundle\DotmailerBundle\Form\EventListener\DataFieldFormSubscriber;

class DataFieldType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_data_field';

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
                'orocrm_dotmailer_integration_select',
                [
                    'label'    => 'orocrm.dotmailer.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'name',
                'text',
                [
                    'label' => 'orocrm.dotmailer.datafield.name.label',
                    'required' => true
                ]
            )
            ->add(
                'type',
                'oro_enum_select',
                [
                    'label' => 'orocrm.dotmailer.datafield.type.label',
                    'tooltip' => 'orocrm.dotmailer.datafield.type.tooltip',
                    'enum_code' => 'dm_df_type',
                    'required' => true,
                ]
            )
            ->add(
                'visibility',
                'oro_enum_select',
                [
                    'label' => 'orocrm.dotmailer.datafield.visibility.label',
                    'tooltip' => 'orocrm.dotmailer.datafield.visibility.tooltip',
                    'enum_code' => 'dm_df_visibility',
                    'required' => true,
                ]
            )
            ->add(
                'defaultValue',
                'text',
                [
                    'label' => 'orocrm.dotmailer.datafield.default_value.label',
                    'tooltip' => 'orocrm.dotmailer.datafield.default_value.tooltip',
                    'required' => false,
                ]
            )
            ->add(
                'notes',
                'textarea',
                [
                    'label' => 'orocrm.dotmailer.datafield.notes.label',
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
                'cascade_validation' => true
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
