<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddressBookHandler
{
    use RequestHandlerTrait;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var DotmailerTransport
     */
    protected $transport;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $manager,
        DotmailerTransport $transport,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->form       = $form;
        $this->requestStack = $requestStack;
        $this->manager    = $manager;
        $this->transport  = $transport;
        $this->translator = $translator;
        $this->logger     = $logger;
    }

    /**
     * Process form
     *
     * @param  AddressBook $entity
     * @return bool
     */
    public function process(AddressBook $entity)
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);
            if ($this->form->isValid()) {
                try {
                    $this->transport->init($entity->getChannel()->getTransport());
                    $apiAddressBook = $this->transport->createAddressBook(
                        $entity->getName(),
                        $this->form->get('visibility')->getData()
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
                    $this->form->addError(new FormError($message));
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Unexpected exception occurred during creating Address Book',
                        ['exception' => $e]
                    );

                    $this->form->addError(
                        new FormError($this->translator->trans('oro.dotmailer.addressbook.message.failed'))
                    );
                }
            }
        }

        return false;
    }
}
