<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldType;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\DotmailerBundle\Provider\Connector\DataFieldConnector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Dotmailer Data Field Controller
 * @Route("/data-field")
 */
class DataFieldController extends AbstractController
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
     * @param DataField $field
     * @return array
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
     * @param DataField $field
     * @return array
     */
    public function infoAction(DataField $field)
    {
        return [
            'entity'  => $field
        ];
    }

    /**
     * Create data field form
     * @Route("/create", name="oro_dotmailer_datafield_create")
     * @Template("@OroDotmailer/DataField/update.html.twig")
     * @Acl(
     *      id="oro_dotmailer_datafield_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroDotmailerBundle:DataField"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $formHandler = $this->get(DataFieldFormHandler::class);
        $form = $formHandler->getForm();
        if ($formHandler->process($request)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.dotmailer.controller.datafield.saved.message')
            );

            return $this->get(Router::class)->redirect(
                $form->getData()
            );
        }

        $isTypeUpdate = $request->get(DataFieldFormHandler::UPDATE_MARKER, false);

        if ($isTypeUpdate) {
            //take different form not to show JS validation on after type update only
            $form = $this->get(FormFactoryInterface::class)
                ->createNamed('oro_dotmailer_data_field_form', DataFieldType::class, $form->getData());
        }

        $field = $form->getData() instanceof DataField ? $form->getData() : new DataField();

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
            'entity_class' => DataField::class
        ];
    }

    /**
     * Run datafield force synchronization
     *
     * @Route(
     *      "/synchronize",
     *      name="oro_dotmailer_datafield_synchronize",
     *      methods={"POST"}
     * )
     * @AclAncestor("oro_dotmailer_datafield_create")
     * @CsrfProtection()
     *
     * @return JsonResponse
     */
    public function synchronizeAction()
    {
        try {
            $repository = $this->get(ManagerRegistry::class)->getRepository('OroIntegrationBundle:Channel');
            $channels = $repository->getConfiguredChannelsForSync(ChannelType::TYPE, true);
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                $this->get(GenuineSyncScheduler::class)->schedule(
                    $channel->getId(),
                    DataFieldConnector::TYPE,
                    [DataFieldConnector::FORCE_SYNC_FLAG => 1]
                );
            }

            $status = Response::HTTP_OK;
            $response = [
                'message' => $this->get(TranslatorInterface::class)
                    ->trans('oro.dotmailer.datafield.syncronize_scheduled')
            ];
        } catch (\Exception $e) {
            $this->get(LoggerInterface::class)->error(
                'Failed to schedule data field synchronization.',
                ['e' => $e]
            );

            $status = Response::HTTP_BAD_REQUEST;
            $response = [
                'message' => $this->get(TranslatorInterface::class)->trans('oro.integration.sync_error')
            ];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            DataFieldFormHandler::class,
            TranslatorInterface::class,
            Router::class,
            FormFactoryInterface::class,
            ManagerRegistry::class,
            GenuineSyncScheduler::class,
            LoggerInterface::class
        ]);
    }
}
