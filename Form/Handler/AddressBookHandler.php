<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The handler for AddressBook form.
 */
class AddressBookHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    private ObjectManager $manager;
    private DotmailerTransport $transport;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;

    public function __construct(
        ObjectManager $manager,
        DotmailerTransport $transport,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->transport = $transport;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function process($entity, FormInterface $form, Request $request)
    {
        $form->setData($entity);
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                try {
                    $this->transport->init($entity->getChannel()->getTransport());
                    $apiAddressBook = $this->transport->createAddressBook(
                        $entity->getName(),
                        $form->get('visibility')->getData()
                    );

                    $entity->setOriginId((string)$apiAddressBook->offsetGet('id'));
                    $this->manager->persist($entity);
                    $this->manager->flush();

                    return true;
                } catch (RestClientException $e) {
                    if ($e->getPrevious() && $e->getPrevious()->getMessage()) {
                        $message = $e->getPrevious()->getMessage();
                    } else {
                        $message = $e->getMessage();
                    }
                    $form->addError(new FormError($message));
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Unexpected exception occurred during creating Address Book',
                        ['exception' => $e]
                    );

                    $form->addError(
                        new FormError($this->translator->trans('oro.dotmailer.addressbook.message.failed'))
                    );
                }
            }
        }

        return false;
    }
}
