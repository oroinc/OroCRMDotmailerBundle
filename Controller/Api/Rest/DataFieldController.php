<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("dotmailer_datafield")
 * @NamePrefix("oro_api_")
 */
class DataFieldController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete dotmailer data field",
     *      resource=true
     * )
     * @Acl(
     *      id="orocrm_dotmailer_datafield_delete",
     *      type="entity",
     *      class="OroCRMDotmailerBundle:DataField",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        try {
            $response = $this->handleDeleteRequest($id);
        } catch (RuntimeException $e) {
            //handle Dotmailer exception and show correct message to the user
            $view = $this->view(
                ['message' => $e->getMessage(), 'code' => Codes::HTTP_INTERNAL_SERVER_ERROR],
                Codes::HTTP_INTERNAL_SERVER_ERROR
            );
            $response = $this->buildResponse(
                $view,
                self::ACTION_DELETE,
                ['id' => $id, 'success' => false],
                Codes::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orocrm_dotmailer.datafield.manager.api');
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
