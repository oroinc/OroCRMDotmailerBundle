<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use FOS\RestBundle\Util\Codes;

use JMS\JobQueueBundle\Entity\Job;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;

use OroCRM\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * @Route("/address-book")
 */
class AddressBookController extends Controller
{
    /**
     * @Route(
     *      "/synchronize/{id}",
     *      name="orocrm_dotmailer_synchronize_adddress_book",
     *      requirements={"id"="\d+"}
     * )
     * @Acl(
     *      id="orocrm_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMDotmailerBundle:AddressBook"
     * )
     * @param AddressBook $addressBook
     *
     * @return JsonResponse
     */
    public function synchronizeAddressBook(AddressBook $addressBook)
    {
        try {
            $job = new Job(
                SyncCommand::COMMAND_NAME,
                [
                    sprintf(
                        '%s=%s',
                        AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION,
                        $addressBook->getId()
                    ),
                    sprintf(
                        '--%s=%s',
                        SyncCommand::INTEGRATION_ID_OPTION,
                        $addressBook->getChannel()->getId()
                    ),
                    '-v'
                ]
            );

            $status = Codes::HTTP_OK;
            $response = [ 'message' => '' ];

            $em = $this->get('doctrine')->getManager();
            $em->persist($job);
            $em->flush();

            $jobViewLink = sprintf(
                '<a href="%s" class="job-view-link">%s</a>',
                $this->get('router')->generate('oro_cron_job_view', ['id' => $job->getId()]),
                $this->get('translator')->trans('oro.integration.progress')
            );

            $response['message'] = str_replace(
                '{{ job_view_link }}',
                $jobViewLink,
                $this->get('translator')->trans('orocrm.dotmailer.addressbook.sync')
            );
        } catch (\Exception $e) {
            $status = Codes::HTTP_BAD_REQUEST;
            $response['message'] = sprintf(
                $this->get('translator')->trans('oro.integration.sync_error'),
                $e->getMessage()
            );
        }

        return new JsonResponse($response, $status);
    }

    /**
     * @Route(
     *      "/marketing-list/disconnect/{id}",
     *      name="orocrm_dotmailer_marketing_list_disconnect",
     *      requirements={"id"="\d+"}
     * )
     * @Acl(
     *      id="orocrm_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMDotmailerBundle:AddressBook"
     * )
     * @param AddressBook $addressBook
     *
     * @return Response
     */
    public function disconnectMarketingListAction(AddressBook $addressBook)
    {
        $em = $this->get('doctrine')
            ->getManager();
        $addressBook->setMarketingList(null);
        $em->persist($addressBook);
        $em->flush($addressBook);

        return new Response();
    }

    /**
     * @Route(
     *      "/widget/manage-connection/marketing-list/{id}",
     *      name="orocrm_dotmailer_marketing_list_connect",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orocrm_marketing_list_update")
     *
     * @Template("OroCRMDotmailerBundle:AddressBook/widget:addressBookConnectionUpdate.html.twig")
     * @param MarketingList $marketingList
     *
     * @return array
     */
    public function addressBookConnectionUpdateAction(MarketingList $marketingList)
    {
        $form = $this->createForm(
            'orocrm_dotmailer_marketing_list_connection',
            null,
            [ 'marketingList' => $marketingList ]
        );

        $addressBook = $this->getAddressBook($marketingList);
        $formData = $addressBook ? ['addressBook' => $addressBook, 'channel' => $addressBook->getChannel()] : [];
        $savedId = $this->get('orocrm_dotmailer.form.handler.connection_update')->handle($form, $formData);

        return [
            'form'    => $form->createView(),
            'entity'  => $addressBook,
            'savedId' => $savedId
        ];
    }

    /**
     * @Route(
     *      "/marketing-list/buttons/{entity}",
     *      name="orocrm_dotmailer_marketing_list_buttons",
     *      requirements={"entity"="\d+"}
     * )
     * @ParamConverter(
     *      "marketingList",
     *      class="OroCRMMarketingListBundle:MarketingList",
     *      options={"id" = "entity"}
     * )
     * @AclAncestor("orocrm_marketing_list_update")
     * @Template()
     *
     * @param MarketingList $marketingList
     *
     * @return array
     */
    public function connectionButtonsAction(MarketingList $marketingList)
    {
        $addressBook = $this->getAddressBook($marketingList);

        return [
            'marketingList' => $marketingList,
            'addressBook' => $addressBook
        ];
    }

    /**
     * @param MarketingList $marketingList
     *
     * @return AddressBook
     */
    protected function getAddressBook(MarketingList $marketingList)
    {
        $addressBook = $this->get('doctrine')
            ->getRepository('OroCRMDotmailerBundle:AddressBook')
            ->findOneBy(['marketingList' => $marketingList]);

        return $addressBook;
    }
}
