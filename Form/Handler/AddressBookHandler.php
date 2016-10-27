<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;

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
     *
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        DotmailerTransport $transport
    ) {
        $this->form      = $form;
        $this->request   = $request;
        $this->manager   = $manager;
        $this->transport = $transport;
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
                    $apiAddressBook = $this->transport
                        ->createAddressBook($entity->getName(), $entity->getVisibility()->getName());

                    $entity->setOriginId($apiAddressBook->offsetGet('id'));
                    $this->manager->persist($entity);
                    $this->manager->flush();

                    return true;
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    if ($exception instanceof RestClientException &&
                        $exception->getPrevious() &&
                        $exception->getPrevious()->getMessage()
                    ) {
                        $message = $exception->getPrevious()->getMessage();
                    }
                    $this->form->addError(new FormError($message));
                }
            }
        }

        return false;
    }
}
