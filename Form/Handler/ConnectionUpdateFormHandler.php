<?php

namespace Oro\Bundle\DotmailerBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class ConnectionUpdateFormHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param RequestStack $requestStack
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack)
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
    }

    /**
     * @param Form  $form
     * @param array $data
     *
     * @return int|null Return id of selected Address book if form valid and null otherwise
     */
    public function handle(Form $form, array $data)
    {
        $form->setData($data);
        $request = $this->requestStack->getCurrentRequest();
        if ($request->isMethod('POST') && $form->submit($request)->isValid()) {
            return $this->onSuccess($form);
        }

        return null;
    }


    /**
     * "Success" form handler
     *
     * @param Form  $form
     *
     * @return int
     */
    protected function onSuccess(Form $form)
    {
        $manager = $this->managerRegistry->getManager();

        $data = $form->getData();
        /** @var AddressBook $addressBook */
        $addressBook = $data['addressBook'];
        $addressBook->setCreateEntities($data['createEntities']);
        $manager->persist($addressBook);
        $manager->flush();

        return $addressBook->getId();
    }
}
