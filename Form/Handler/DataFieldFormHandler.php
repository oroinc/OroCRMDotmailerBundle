<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form handler for Dotmailer DataField.
 */
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

    /** @var DataFieldManager */
    protected $dataFieldManager;

    public function __construct(
        FormInterface $form,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        DataFieldManager $dataFieldManager
    ) {
        $this->form = $form;
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->dataFieldManager = $dataFieldManager;
    }

    /**
     * @param Request $request
     *
     * @return bool Return true if form is valid and Data Field was created in DM
     * and false otherwise
     */
    public function process(Request $request): bool
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            $this->form->handleRequest($request);
            if (!$request->get(self::UPDATE_MARKER, false)
                && $this->form->isSubmitted()
                && $this->form->isValid()
            ) {
                return $this->onSuccess($this->form->getData());
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

    protected function handleGeneralException(\Exception $e)
    {
        $this->form->addError(
            new FormError($this->translator->trans('oro.dotmailer.handler.unable_to_create_field'))
        );
        $this->logger->error('Failed to create field in Dotmailer', ['exception' => $e]);
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }
}
