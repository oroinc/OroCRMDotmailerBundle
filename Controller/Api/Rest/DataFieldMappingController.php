<?php

namespace Oro\Bundle\DotmailerBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("dotmailer_datafield_mapping")
 * @NamePrefix("oro_api_")
 */
class DataFieldMappingController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete dotmailer data field mapping",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_dotmailer_datafield_mapping_delete",
     *      type="entity",
     *      class="OroDotmailerBundle:DataFieldMapping",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_dotmailer.datafield_mapping.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
