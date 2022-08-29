<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves CRUD of DataFieldMapping entity.
 *
 * @Route("/data-field-mapping")
 */
class DataFieldMappingController extends AbstractController
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
    public function indexAction(): array
    {
        return [
            'entity_class' => DataFieldMapping::class
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_dotmailer_datafield_mapping_update", requirements={"id"="\d+"}))
     * @Acl(
     *      id="oro_dotmailer_datafield_mapping_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDotmailerBundle:DataFieldMapping"
     * )
     * @Template("@OroDotmailer/DataFieldMapping/update.html.twig")
     */
    public function updateAction(DataFieldMapping $mapping): array|RedirectResponse
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
     * @Template("@OroDotmailer/DataFieldMapping/update.html.twig")
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new DataFieldMapping());
    }

    protected function update(DataFieldMapping $mapping): array|RedirectResponse
    {
        $form = $this->get(FormFactoryInterface::class)
            ->createNamed('oro_dotmailer_datafield_mapping_form', DataFieldMappingType::class);

        $response = $this->get(UpdateHandlerFacade::class)->update(
            $mapping,
            $form,
            $this->get(TranslatorInterface::class)->trans('oro.dotmailer.controller.datafield_mapping.saved.message')
        );

        if (\is_array($response)) {
            $response = array_merge(
                $response,
                [
                    'entities' => $this->get('oro_dotmailer.entity_provider')->getEntities()
                ]
            );
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_dotmailer.entity_provider' => EntityProvider::class,
                TranslatorInterface::class,
                FormFactoryInterface::class,
                UpdateHandlerFacade::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
