<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * @Route("/address-book")
 */
class AddressBookController extends Controller
{
    /**
     * @Route(
     *      "/syncronize/{entity}",
     *      name="orocrm_dotmailer_syncronize_adddress_book",
     *      requirements={"entity"="\d+"}
     * )
     * @Acl(
     *      id="orocrm_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMDotmailerBundle:AddressBook"
     * )
     */
    public function synchronizeAddressBook(AddressBook $addressBook)
    {
        return new Response();
    }

    /**
     * @Route(
     *      "/marketing-list/disconnect/{entity}",
     *      name="orocrm_dotmailer_marketing_list_disconnect",
     *      requirements={"entity"="\d+"}
     * )
     * @Acl(
     *      id="orocrm_dotmailer_address_book_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMDotmailerBundle:AddressBook"
     * )
     */
    public function disconnectMarketingListAction(AddressBook $addressBook)
    {
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
     */
    public function addressBookConnectionUpdateAction(MarketingList $marketingList)
    {
        $addressBook = $this->getAddressBook($marketingList);

        $form = $this->createForm(
            'orocrm_dotmailer_marketing_list_connection',
            [
                'addressBook' => $addressBook,
            ],
            [
                'marketingList' => $marketingList
            ]
        );

        $em = $this->get('doctrine')->getManager();
        $request = $this->getRequest();
        if ($request->isMethod('POST')) {
            if ($form->submit($request)->isValid()) {
                $addressBook = $form->getData();
                $em->persist($addressBook);
                $em->flush();
            }
        }

        return [
            'form' => $form,
            'entity' => $addressBook
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
     *
     * @Template()
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
