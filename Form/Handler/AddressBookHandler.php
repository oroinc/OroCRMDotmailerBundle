<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use OroCRM\Bundle\DotmailerBundle\Exception\RestClientException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;

class AddressBookHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

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

    /**
     *
     * @param FormInterface       $form
     * @param Request             $request
     * @param ObjectManager       $manager
     * @param DotmailerTransport  $transport
     * @param TranslatorInterface $translator
     * @param LoggerInterface     $logger
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        DotmailerTransport $transport,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->form       = $form;
        $this->request    = $request;
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

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);
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
                        new FormError($this->translator->trans('orocrm.dotmailer.addressbook.message.failed'))
                    );
                }
            }
        }

        return false;
    }
}
