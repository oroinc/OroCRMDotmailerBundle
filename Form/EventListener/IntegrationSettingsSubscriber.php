<?php

namespace Oro\Bundle\DotmailerBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class IntegrationSettingsSubscriber implements EventSubscriberInterface
{
    /**
     * @var Mcrypt
     */
    protected $encoder;

    /**
     * @param Mcrypt $encoder
     */
    public function __construct(Mcrypt $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        ];
    }

    /**
     * Populate websites choices if exist in entity
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

        if ($data->getId()) {
            // change label for apiKey field
            FormUtils::replaceField(
                $form,
                'password',
                [
                    'label' => 'oro.dotmailer.integration_transport.password.label',
                    'tooltip' => 'oro.dotmailer.form.password.tooltip',
                    'required' => false,
                ],
                ['constraints']
            );
        }
    }

    /**
     * Pre submit event listener
     * Encrypt passwords/client key and populate if empty
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = (array)$event->getData();
        $form = $event->getForm();

        $oldPassword = $form->get('password')->getData();
        if (empty($data['password']) && $oldPassword) {
            // populate old password
            $data['password'] = $oldPassword;
        } elseif (isset($data['password'])) {
            $data['password'] = $this->encoder->encryptData($data['password']);
        }

        $oldClientKey = $form->get('clientKey')->getData();
        if (empty($data['clientKey']) && $oldClientKey) {
            // populate old client key
            $data['clientKey'] = $oldClientKey;
        } elseif (isset($data['clientKey'])) {
            $data['clientKey'] = $this->encoder->encryptData($data['clientKey']);
        }

        $event->setData($data);
    }
}
