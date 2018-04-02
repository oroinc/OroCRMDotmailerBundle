<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/data-field-mapping")
 */
class DataFieldMappingController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_dotmailer_datafield_mapping_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * @AclAncestor("oro_dotmailer_datafield_mapping_update")
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_dotmailer.entity.datafield_mapping.class')
        ];
    }

    /**
     * @param DataFieldMapping $mapping
     * @return array
     *
     * @Route("/update/{id}", name="oro_dotmailer_datafield_mapping_update", requirements={"id"="\d+"}))
     * @Acl(
     *      id="oro_dotmailer_datafield_mapping_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDotmailerBundle:DataFieldMapping"
     * )
     * @Template("OroDotmailerBundle:DataFieldMapping:update.html.twig")
     */
    public function updateAction(DataFieldMapping $mapping)
    {
        return $this->update($mapping);
    }

    /**
     * @Route("/create", name="oro_dotmailer_datafield_mapping_create"))
     * @Acl(
     *      id="oro_dotmailer_datafield_mapping_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroDotmailerBundle:DataFieldMapping"
     * )
     * @Template("OroDotmailerBundle:DataFieldMapping:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new DataFieldMapping());
    }

    /**
     * @param DataFieldMapping $mapping
     * @return array
     */
    protected function update(DataFieldMapping $mapping)
    {
        $response = $this->get('oro_form.model.update_handler')->update(
            $mapping,
            $this->get('oro_dotmailer.datafield_mapping.form'),
            $this->get('translator')->trans('oro.dotmailer.controller.datafield_mapping.saved.message')
        );

        if (is_array($response)) {
            $response = array_merge(
                $response,
                [
                    'entities' => $this->get('oro_dotmailer.entity_provider')->getEntities()
                ]
            );
        }

        return $response;
    }
}
