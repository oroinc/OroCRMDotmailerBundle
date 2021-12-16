<?php

namespace Oro\Bundle\DotmailerBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for DataField entity.
 */
class DataFieldController extends RestController
{
    /**
     * @ApiDoc(
     *      description="Delete dotdigital data field",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_dotmailer_datafield_delete",
     *      type="entity",
     *      class="OroDotmailerBundle:DataField",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction(int $id)
    {
        try {
            $response = $this->handleDeleteRequest($id);
        } catch (RuntimeException $e) {
            //handle Dotmailer exception and show correct message to the user
            $view = $this->view(
                ['message' => $e->getMessage(), 'code' => Response::HTTP_INTERNAL_SERVER_ERROR],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
            $response = $this->buildResponse(
                $view,
                self::ACTION_DELETE,
                ['id' => $id, 'success' => false],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_dotmailer.datafield.manager.api');
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
