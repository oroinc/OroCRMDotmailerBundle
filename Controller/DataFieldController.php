<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Form\Type\DataFieldType;

/**
 * @Route("/data-field")
 */
class DataFieldController extends Controller
{
    /**
     * @Route("/view/{id}", name="orocrm_dotmailer_datafield_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orocrm_dotmailer_datafield_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroCRMDotmailerBundle:DataField"
     * )
     */
    public function viewAction(DataField $field)
    {
        return [
            'entity' => $field
        ];
    }

    /**
     * @Route("/info/{id}", name="orocrm_dotmailer_datafield_info", requirements={"id"="\d+"})
     * @AclAncestor("orocrm_dotmailer_datafield_view")
     * @Template()
     */
    public function infoAction(DataField $field)
    {
        return array(
            'entity'  => $field
        );
    }

    /**
     * Create data field form
     * @Route("/create", name="orocrm_dotmailer_datafield_create")
     * @Template("OroCRMDotmailerBundle:DataField:update.html.twig")
     * @Acl(
     *      id="orocrm_dotmailer_datafield_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroCRMDotmailerBundle:DataField"
     * )
     */
    public function createAction()
    {
        $field = new DataField();
        $form = $this->createForm(DataFieldType::NAME);
        return $this->get('oro_form.model.update_handler')->update(
            $field,
            $form,
            $this->get('translator')->trans('orocrm.dotmailer.controller.datafield.saved.message')
        );
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="orocrm_dotmailer_datafield_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * @AclAncestor("orocrm_dotmailer_datafield_view")
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orocrm_dotmailer.entity.datafield.class')
        ];
    }
}
