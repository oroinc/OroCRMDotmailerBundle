<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Handler\AddressBookHandler;
use Oro\Bundle\DotmailerBundle\Form\Handler\ConnectionUpdateFormHandler;
use Oro\Bundle\DotmailerBundle\Form\Type\MarketingListConnectionType;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Dotmailer Address Book Controller
 * @Route("/address-book")
 */
class AddressBookController extends AbstractController
{
    /**
     * @Route(
     *      "/synchronize/{id}",
     *      name="oro_dotmailer_synchronize_adddress_book",
     *      requirements={"id"="\d+"},
     *      methods={"POST"}
     * )
     * @Acl(
     *      id="oro_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDotmailerBundle:AddressBook"
     * )
     * @CsrfProtection()
     */
    public function synchronizeAddressBookAction(AddressBook $addressBook): JsonResponse
    {
        $translator = $this->get(TranslatorInterface::class);

        try {
            $this->get(GenuineSyncScheduler::class)->schedule(
                $addressBook->getChannel()->getId(),
                null,
                [AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $addressBook->getId()]
            );

            $status = Response::HTTP_OK;
            $response = [
                'message' => str_replace(
                    '{{ job_view_link }}',
                    '',
                    $translator->trans('oro.dotmailer.addressbook.sync')
                )
            ];
        } catch (\Exception $e) {
            $this->get(LoggerInterface::class)->error(
                sprintf(
                    'Failed to schedule address book synchronization. Address Book Id: %s.',
                    $addressBook->getId()
                ),
                ['e' => $e]
            );

            $status = Response::HTTP_BAD_REQUEST;
            $response = [
                'message' => $translator->trans('oro.integration.sync_error')
            ];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * @Route(
     *      "/synchronize_datafields/{id}",
     *      name="oro_dotmailer_synchronize_adddress_book_datafields",
     *      requirements={"id"="\d+"},
     *      methods={"POST"}
     * )
     * @Acl(
     *      id="oro_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDotmailerBundle:AddressBook"
     * )
     * @CsrfProtection()
     */
    public function synchronizeAddressBookDataFieldsAction(AddressBook $addressBook): JsonResponse
    {
        $translator = $this->get(TranslatorInterface::class);

        try {
            $this->getDoctrine()->getRepository('OroDotmailerBundle:AddressBookContact')
                ->bulkEntityUpdatedByAddressBook($addressBook);

            $status = Response::HTTP_OK;
            $response = [
                'message' => $translator->trans('oro.dotmailer.addressbook.sync_datafields_success')
            ];
        } catch (\Exception $e) {
            $status = Response::HTTP_BAD_REQUEST;
            $response['message'] = sprintf(
                $translator->trans('oro.dotmailer.addressbook.sync_datafields_failed'),
                $e->getMessage()
            );
        }

        return new JsonResponse($response, $status);
    }

    /**
     * @Route(
     *      "/marketing-list/disconnect/{id}",
     *      name="oro_dotmailer_marketing_list_disconnect",
     *      requirements={"id"="\d+"},
     *      methods={"DELETE"}
     * )
     * @Acl(
     *      id="oro_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDotmailerBundle:AddressBook"
     * )
     * @CsrfProtection()
     */
    public function disconnectMarketingListAction(AddressBook $addressBook): JsonResponse
    {
        $em = $this->get('doctrine')
            ->getManager();
        $addressBook->setMarketingList(null);
        $em->persist($addressBook);
        $em->flush($addressBook);

        return new JsonResponse();
    }

    /**
     * @Route(
     *      "/widget/manage-connection/marketing-list/{id}",
     *      name="oro_dotmailer_marketing_list_connect",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_marketing_list_update")
     * @Template("@OroDotmailer/AddressBook/widget/addressBookConnectionUpdate.html.twig")
     */
    public function addressBookConnectionUpdateAction(MarketingList $marketingList): array
    {
        $form = $this->createForm(
            MarketingListConnectionType::class,
            null,
            [ 'marketingList' => $marketingList ]
        );

        $addressBook = $this->getAddressBook($marketingList);
        $formData = $addressBook
            ? [
                'addressBook'      => $addressBook,
                'channel'          => $addressBook->getChannel(),
                'createEntities'   => $addressBook->isCreateEntities()
            ]
            : [];
        $savedId = $this->get(ConnectionUpdateFormHandler::class)->handle($form, $formData);

        return [
            'form'    => $form->createView(),
            'entity'  => $addressBook,
            'savedId' => $savedId
        ];
    }

    /**
     * @Route(
     *      "/marketing-list/buttons/{entity}",
     *      name="oro_dotmailer_marketing_list_buttons",
     *      requirements={"entity"="\d+"}
     * )
     * @ParamConverter(
     *      "marketingList",
     *      class="OroMarketingListBundle:MarketingList",
     *      options={"id" = "entity"}
     * )
     * @Template()
     */
    public function connectionButtonsAction(MarketingList $marketingList): array
    {
        if (!$this->isGranted('orocrm_marketing_list_update') ||
            !$this->isGranted('orocrm_dotmailer_address_book_update')
        ) {
            throw new AccessDeniedException();
        }
        $addressBook = $this->getAddressBook($marketingList);

        return [
            'marketingList' => $marketingList,
            'addressBook' => $addressBook
        ];
    }

    protected function getAddressBook(MarketingList $marketingList):? AddressBook
    {
        $addressBook = $this->get('doctrine')
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->findOneBy(['marketingList' => $marketingList]);

        return $addressBook;
    }

    /**
     * @Route(
     *      "/create",
     *      name="oro_dotmailer_address_book_create"
     * )
     * @Acl(
     *      id="oro_address_book_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroDotmailerBundle:AddressBook"
     * )
     * @Template("@OroDotmailer/AddressBook/update.html.twig")
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new AddressBook());
    }

    protected function update(AddressBook $addressBook): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $addressBook,
            $this->get('oro_dotmailer.form.address_book'),
            $this->get(TranslatorInterface::class)->trans('oro.dotmailer.addressbook.message.saved'),
            null,
            $this->get(AddressBookHandler::class)
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                LoggerInterface::class,
                GenuineSyncScheduler::class,
                ConnectionUpdateFormHandler::class,
                AddressBookHandler::class,
                'oro_dotmailer.form.address_book' => Form::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
