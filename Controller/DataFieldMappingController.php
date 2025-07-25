<?php

namespace Oro\Bundle\DotmailerBundle\Controller;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;
use Oro\Bundle\DotmailerBundle\Form\Type\DataFieldMappingType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Serves CRUD of DataFieldMapping entity.
 */
#[Route(path: '/data-field-mapping')]
class DataFieldMappingController extends AbstractController
{
    #[Route(
        path: '/{_format}',
        name: 'oro_dotmailer_datafield_mapping_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[AclAncestor('oro_dotmailer_datafield_mapping_update')]
    public function indexAction(): array
    {
        return [
            'entity_class' => DataFieldMapping::class
        ];
    }

    #[Route(path: '/update/{id}', name: 'oro_dotmailer_datafield_mapping_update', requirements: ['id' => '\d+'])]
    #[Template('@OroDotmailer/DataFieldMapping/update.html.twig')]
    #[Acl(
        id: 'oro_dotmailer_datafield_mapping_update',
        type: 'entity',
        class: DataFieldMapping::class,
        permission: 'EDIT'
    )]
    public function updateAction(DataFieldMapping $mapping): array|RedirectResponse
    {
        return $this->update($mapping);
    }

    #[Route(path: '/create', name: 'oro_dotmailer_datafield_mapping_create')]
    #[Template('@OroDotmailer/DataFieldMapping/update.html.twig')]
    #[Acl(
        id: 'oro_dotmailer_datafield_mapping_create',
        type: 'entity',
        class: DataFieldMapping::class,
        permission: 'CREATE'
    )]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new DataFieldMapping());
    }

    protected function update(DataFieldMapping $mapping): array|RedirectResponse
    {
        $form = $this->container->get(FormFactoryInterface::class)
            ->createNamed('oro_dotmailer_datafield_mapping_form', DataFieldMappingType::class);

        $response = $this->container->get(UpdateHandlerFacade::class)->update(
            $mapping,
            $form,
            $this->container->get(TranslatorInterface::class)
                ->trans('oro.dotmailer.controller.datafield_mapping.saved.message')
        );

        if (\is_array($response)) {
            $response = array_merge(
                $response,
                [
                    'entities' => $this->container->get('oro_dotmailer.entity_provider')->getEntities()
                ]
            );
        }

        return $response;
    }

    #[\Override]
    public static function getSubscribedServices(): array
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
