<?php

namespace OroCRM\Bundle\DotmailerBundle\Controller;

use FOS\RestBundle\Util\Codes;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroCRM\Bundle\DotmailerBundle\Entity\DataField;
use OroCRM\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;

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
        if ($this->get('orocrm_dotmailer.form.handler.datafield_update')->process($field)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('orocrm.dotmailer.controller.datafield.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($field);
        }

        $isTypeUpdate = $this->get('request')->get(DataFieldFormHandler::UPDATE_MARKER, false);

        $form = $this->get('orocrm_dotmailer.datafield.form');
        if ($isTypeUpdate) {
            //take different form not to show JS validation on after typ update only
            $form = $this->get('form.factory')
                ->createNamed('orocrm_dotmailer_data_field_form', 'orocrm_dotmailer_data_field', $form->getData());
        }

        return [
            'entity' => $field,
            'form'   => $form->createView()
        ];
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

    /**
     * Run datafield force synchronization
     *
     * @Route(
     *      "/synchronize",
     *      name="orocrm_dotmailer_datafield_synchronize"
     * )
     * @AclAncestor("orocrm_dotmailer_datafield_create")
     *
     * @return JsonResponse
     */
    public function synchronize()
    {
        try {
            $job = new Job(
                SyncCommand::COMMAND_NAME,
                [
                    sprintf(
                        '%s=%s',
                        DataFieldConnector::FORCE_SYNC_FLAG,
                        1
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
                $this->get('translator')->trans('orocrm.dotmailer.datafield.syncronize_scheduled')
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
}
