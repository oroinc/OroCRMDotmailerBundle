<?php

namespace Oro\Bundle\DotmailerBundle\Form\Extension;

use Oro\Bundle\DotmailerBundle\Provider\ChannelType as ChannelTypeProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Extension for channel connectors.
 */
class ChannelConnectorsExtension extends AbstractTypeExtension
{
    const CLASS_PATH = '[attr][class]';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'onPostSetData']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'onPostSubmit']
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmit']
        );
    }

    /**
     * @param Channel $data
     * @return bool
     */
    public function isApplicable(Channel $data = null)
    {
        return $data && $data->getType() === ChannelTypeProvider::TYPE;
    }

    /**
     * Hide connectors for Dotmailer channel and
     * remove synchronizationSettings for Dotmailer channel
     */
    public function onPostSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isApplicable($data)) {
            return;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $options          = $event->getForm()['connectors']->getConfig()->getOptions();
        $class            = $propertyAccessor->getValue($options, self::CLASS_PATH);

        $event->getForm()->remove('synchronizationSettings');

        FormUtils::replaceField(
            $event->getForm(),
            'connectors',
            [
                'attr' => [
                    'class' => implode(' ', [$class, 'hide'])
                ]
            ]
        );
    }

    /**
     * Set all connectors to Dotmailer channel
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isApplicable($data)) {
            return;
        }
        $options = $event->getForm()['connectors']->getConfig()->getOptions();
        $connectors = array_values($options['choices']);
        $data->setConnectors($connectors);
    }

    /**
     * Remove synchronizationSettings for Dotmailer channel
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getForm()->getData();
        if (!$this->isApplicable($data)) {
            return;
        }

        $event->getForm()->remove('synchronizationSettings');
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}
