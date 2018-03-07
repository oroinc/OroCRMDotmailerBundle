<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Oro\Bundle\DotmailerBundle\Form\EventListener\DataFieldMappingFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataFieldMappingType extends AbstractType
{
    const NAME = 'oro_dotmailer_datafield_mapping';

    /** @var string */
    protected $dataClass;

    /** @var DataFieldMappingFormSubscriber */
    protected $subscriber;

    /**
     * @param string $dataClass
     * @param DataFieldMappingFormSubscriber $subscriber
     */
    public function __construct($dataClass, DataFieldMappingFormSubscriber $subscriber)
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
                'entity',
                'oro_marketing_list_contact_information_entity_choice',
                [
                    'label' => 'oro.dotmailer.datafieldmapping.entity.label',
                    'required' => true
                ]
            )
            ->add(
                'syncPriority',
                'integer',
                [
                    'label' => 'oro.dotmailer.datafieldmapping.sync_priority.label',
                    'tooltip' => 'oro.dotmailer.datafieldmapping.sync_priority.tooltip',
                    'required' => false,
                ]
            )
            ->add(
                'config',
                'oro_dotmailer_datafield_mapping_config',
                [
                    'validation_groups' => false,
                    'mapped'             => false,
                    'auto_initialize'    => false,
                ]
            )
            ->add(
                'config_source',
                'hidden',
                [
                    'mapped' => false,
                ]
            )
            ->add(
                'configs',
                'oro_collection',
                [
                    'handle_primary' => false,
                    'entry_type' => DataFieldMappingConfigType::NAME
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        //don't render config collection, mapping configuration is updated in subscriber
        $view->children['configs']->setRendered();
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
