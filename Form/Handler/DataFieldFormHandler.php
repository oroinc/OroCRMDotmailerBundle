<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class DataFieldFormHandler
{
    const UPDATE_MARKER = 'formUpdateMarker';

    /** @var LoggerInterface */
    protected $logger;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FormInterface */
    protected $form;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var RequestStack */
    protected $requestStack;

    /** @var DataFieldManager */
    protected $dataFieldManager;

    /**
     * @param FormInterface $form
     * @param ManagerRegistry $managerRegistry
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param DataFieldManager $dataFieldManager
     */
    public function __construct(
        FormInterface $form,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        DataFieldManager $dataFieldManager
    ) {
        $this->form = $form;
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->dataFieldManager = $dataFieldManager;
    }

    /**
     * @param DataField $entity
     * @return bool Return true if form is valid and Data Field was created in DM
     * and false otherwise
     */
    public function process(DataField $entity)
    {
        $this->form->setData($entity);
        $request = $this->requestStack->getCurrentRequest();
        if ($request->isMethod('POST')) {
            $this->form->submit($request);
            if (!$request->get(self::UPDATE_MARKER, false) && $this->form->isValid()) {
                return $this->onSuccess($entity);
            }
        }

        return false;
    }

    /**
     * Create data field in Dotmailer and save entity only if field was created successfully
     *
     * @param DataField $entity
     * @return bool
     */
    protected function onSuccess(DataField $entity)
    {
        $originWasCreated = false;
        try {
            $this->dataFieldManager->createOriginDataField($entity);
            $originWasCreated = true;
        } catch (InvalidDefaultValueException $e) {
            $this->form->addError(
                new FormError(
                    sprintf(
                        "%s %s",
                        $this->translator->trans('oro.dotmailer.handler.default_value_not_match'),
                        $e->getMessage()
                    )
                )
            );
        } catch (RestClientException $e) {
            if ($e->getPrevious()) {
                $this->form->addError(
                    new FormError(
                        sprintf(
                            "%s %s",
                            $this->translator->trans('oro.dotmailer.handler.unable_to_create_field'),
                            $e->getPrevious()->getMessage()
                        )
                    )
                );
            } else {
                $this->handleGeneralException($e);
            }
        } catch (\Exception $e) {
            $this->handleGeneralException($e);
        }
        if ($originWasCreated) {
            $manager = $this->managerRegistry->getManager();
            $manager->persist($entity);
            $manager->flush();
        }

        return $originWasCreated;
    }

    /**
     * @param \Exception $e
     */
    protected function handleGeneralException(\Exception $e)
    {
        $this->form->addError(
            new FormError($this->translator->trans('oro.dotmailer.handler.unable_to_create_field'))
        );
        $this->logger->error('Failed to create field in Dotmailer', ['exception' => $e]);
    }
}
