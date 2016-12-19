<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;

class DataFieldFormSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * Update defaultValue type based on chosen field type
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data === null) {
            return;
        }

        if ($data->getType()) {
            $this->changeDefaultValueBasedOnType($data->getType()->getId(), $form);
        }
    }

    /**
     * Update defaultValue type based on chosen field type
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!empty($data['type'])) {
            $this->changeDefaultValueBasedOnType($data['type'], $form);
        }
    }

    /**
     * @param string $type
     * @param FormInterface $form
     */
    protected function changeDefaultValueBasedOnType($type, $form)
    {
        switch ($type) {
            case DataField::FIELD_TYPE_DATE:
                $this->updateDefaultValueField($form, 'oro_date');
                break;
            case DataField::FIELD_TYPE_BOOLEAN:
                $this->updateDefaultValueField(
                    $form,
                    'choice',
                    [
                        'choices' => [
                            'Yes' => DataField::DEFAULT_BOOLEAN_YES,
                            'No' => DataField::DEFAULT_BOOLEAN_NO
                        ]
                    ]
                );
                break;
        }
    }

    /**
     * @param FormInterface $form
     * @param string $type
     * @param array $additionalOptions
     */
    protected function updateDefaultValueField(FormInterface $form, $type, $additionalOptions = [])
    {
        $defaultOptions = [
            'label' => 'orocrm.dotmailer.datafield.default_value.label',
            'required' => false
        ];
        $form->remove('defaultValue');
        $form->add(
            'defaultValue',
            $type,
            array_merge($defaultOptions, $additionalOptions)
        );
    }
}
