<?php

namespace Oro\Bundle\DotmailerBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for DataFieldMapping entity.
 */
class DataFieldMappingController extends RestController
{
    /**
     * @ApiDoc(
     *      description="Delete dotdigital data field mapping",
     *      resource=true
     * )
     *
     * @param int $id
     * @return Response
     */
    #[Acl(
        id: 'oro_dotmailer_datafield_mapping_delete',
        type: 'entity',
        class: DataFieldMapping::class,
        permission: 'DELETE'
    )]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get entities with fields
     *
     * @ApiDoc(
     *      description="Get entities with fields",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function fieldsAction()
    {
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->container->get('oro_dotmailer.entity_field_list_provider');
        $statusCode = Response::HTTP_OK;
        try {
            $result = $provider->getFields(true, true);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_dotmailer.datafield_mapping.manager.api');
    }

    #[\Override]
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    #[\Override]
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
