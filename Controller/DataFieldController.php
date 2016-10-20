<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;

/**
 * @Route("/data-field")
 */
class DataFieldController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_dotmailer_datafield_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_dotmailer_datafield_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroDotmailerBundle:DataField"
     * )
     */
    public function viewAction(DataField $field)
    {
        return [
            'entity' => $field
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_dotmailer_datafield_info", requirements={"id"="\d+"})
     * @AclAncestor("oro_dotmailer_datafield_view")
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
     * @Route("/create", name="oro_dotmailer_datafield_create")
     * @Template("OroDotmailerBundle:DataField:update.html.twig")
     * @Acl(
     *      id="oro_dotmailer_datafield_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroDotmailerBundle:DataField"
     * )
     */
    public function createAction()
    {
        $field = new DataField();
        $form = $this->createForm(DataFieldType::NAME);
        return $this->get('oro_form.model.update_handler')->update(
            $field,
            $form,
            $this->get('translator')->trans('oro.dotmailer.controller.datafield.saved.message')
        );
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_dotmailer_datafield_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * @AclAncestor("oro_dotmailer_datafield_view")
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_dotmailer.entity.datafield.class')
        ];
    }
}
