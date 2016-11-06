<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use FOS\RestBundle\Util\Codes;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;

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
        if ($this->get('oro_dotmailer.form.handler.datafield_update')->process($field)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.dotmailer.controller.datafield.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($field);
        }

        $isTypeUpdate = $this->get('request')->get(DataFieldFormHandler::UPDATE_MARKER, false);

        $form = $this->get('oro_dotmailer.datafield.form');
        if ($isTypeUpdate) {
            //take different form not to show JS validation on after typ update only
            $form = $this->get('form.factory')
                ->createNamed('oro_dotmailer_data_field_form', 'oro_dotmailer_data_field', $form->getData());
        }

        return [
            'entity' => $field,
            'form'   => $form->createView()
        ];
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

    /**
     * Run datafield force synchronization
     *
     * @Route(
     *      "/synchronize",
     *      name="oro_dotmailer_datafield_synchronize"
     * )
     * @AclAncestor("oro_dotmailer_datafield_create")
     *
     * @return JsonResponse
     */
    public function synchronize()
    {
        try {
            $repository = $this->get('doctrine')->getRepository('OroIntegrationBundle:Channel');
            $channels = $repository->getConfiguredChannelsForSync(ChannelType::TYPE, true);
            foreach ($channels as $channel) {
                $job = new Job(
                    SyncCommand::COMMAND_NAME,
                    [
                        sprintf(
                            '--%s=%s',
                            SyncCommand::INTEGRATION_ID_OPTION,
                            $channel->getId()
                        ),
                        sprintf(
                            '--%s=%s',
                            'connector',
                            DataFieldConnector::TYPE
                        ),
                        sprintf(
                            '%s=%s',
                            DataFieldConnector::FORCE_SYNC_FLAG,
                            1
                        ),
                        '-v'
                    ]
                );

                $em = $this->get('doctrine')->getManager();
                $em->persist($job);
                $em->flush();
            }
            $status = Codes::HTTP_OK;
            $response = ['message' => $this->get('translator')->trans('oro.dotmailer.datafield.syncronize_scheduled')];
        } catch (\Exception $e) {
            $status = Codes::HTTP_BAD_REQUEST;
            $response['message'] = sprintf(
                $this->get('translator')->trans('oro.integration.sync_error'),
                $e->getMessage()
            );
        }

        return new JsonResponse($response, $status);
    }
}
