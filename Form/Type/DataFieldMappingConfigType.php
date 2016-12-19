<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataFieldMappingConfigType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_datafield_mapping_config';

    /** @var string */
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
                'entityFields',
                'hidden',
                [
                    'label'    => 'orocrm.dotmailer.datafieldmappingconfig.entity_fields.label',
                    'required' => true,
                ]
            )
            ->add(
                'dataField',
                'orocrm_dotmailer_datafield_select',
                [
                    'label'    => '',
                    'required' => true,
                    'channel_field' => 'channel',
                ]
            )
            ->add(
                'isTwoWaySync',
                'checkbox',
                [
                    'label' => 'orocrm.dotmailer.datafieldmappingconfig.is_two_way_sync.label',
                    'tooltip' => 'orocrm.dotmailer.datafieldmappingconfig.is_two_way_sync.tooltip',
                    'required' => false,
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
